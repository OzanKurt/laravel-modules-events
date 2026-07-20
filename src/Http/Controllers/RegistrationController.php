<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kurt\Modules\Core\Http\Concerns\HandlesApiQuery;
use Kurt\Modules\Core\Http\Controllers\ApiController;
use Kurt\Modules\Events\Flow\Exceptions\QueueChallengeFailed;
use Kurt\Modules\Events\Flow\Exceptions\SelfCancellationNotPermitted;
use Kurt\Modules\Events\Http\Concerns\ResolvesActingUser;
use Kurt\Modules\Events\Http\Resources\OrderResource;
use Kurt\Modules\Events\Http\Resources\TicketResource;
use Kurt\Modules\Events\Support\Events as EventsService;
use Kurt\Modules\Events\Ticketing\Exceptions\PriceTierSoldOut;
use Kurt\Modules\Events\Ticketing\Exceptions\TicketNotCheckInable;
use Kurt\Modules\Events\Ticketing\Exceptions\TicketTypeSoldOut;
use Kurt\Modules\Events\Ticketing\Models\Order;
use Kurt\Modules\Events\Ticketing\Models\Ticket;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

/**
 * Registrations / tickets: register, list a user's registrations, buyer
 * self-cancel, and door check-in.
 *
 * Every write is a thin adapter over the Support\Events domain service, so the
 * v1.2.0 enforcement — sale-queue admission, ticket-type + price-tier capacity,
 * the refund/cancel window, and check-in replay protection — all run exactly as
 * they do internally. This controller never reimplements or bypasses those
 * checks; it translates their exceptions into clean HTTP responses.
 */
final class RegistrationController extends ApiController
{
    use HandlesApiQuery;
    use ResolvesActingUser;

    public function __construct(private readonly EventsService $events) {}

    /**
     * Register the authenticated user (and any additional holders) for a ticket
     * type. Runs the full reservation path so capacity, price-tier caps and the
     * sale-queue gate are enforced by the domain, not the API.
     */
    public function register(Request $request, TicketType $ticketType): JsonResponse
    {
        $event = $ticketType->event()->firstOrFail();
        $this->authorize('view', $event);

        $user = $this->actingUser($request);

        $data = $this->validate($request, [
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:'.$ticketType->max_per_order],
            'discount_code' => ['sometimes', 'nullable', 'string'],
            'holders' => ['sometimes', 'array', 'max:'.$ticketType->max_per_order],
            'holders.*.name' => ['required_with:holders', 'string', 'max:255'],
            'holders.*.email' => ['required_with:holders', 'email'],
        ]);

        $holders = $this->resolveHolders($data, $user);
        $discountCode = isset($data['discount_code']) ? (string) $data['discount_code'] : null;

        try {
            $order = $this->events->reserve($ticketType, $user, count($holders), $holders, $discountCode);
        } catch (TicketTypeSoldOut|PriceTierSoldOut) {
            return $this->fail('This ticket type is sold out.', 409);
        } catch (QueueChallengeFailed) {
            return $this->fail('You must be admitted from the sale queue before registering.', 409);
        }

        // Free registrations are finalised immediately so a ticket is issued on
        // the happy path. Paid orders stay Pending for the consumer's own
        // payment flow (the module is payment-agnostic).
        if ($order->total_minor === 0) {
            $this->events->pay($order, 'free', 'free-'.$order->id);
            $order = $order->refresh();
        }

        return $this->respondCreated(OrderResource::make($order->load('tickets')));
    }

    /**
     * The authenticated user's own registrations (tickets they hold).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->actingUser($request);

        $query = Ticket::query()->where('holder_id', $user->getKey());
        $query = $this->applyApiFilters($query, $request, ['event_id' => 'exact', 'status' => 'exact']);
        $query = $this->applyApiSorts($query, $request, ['created_at']);

        return $this->respondPaginated($this->apiPaginate($query, $request), TicketResource::class);
    }

    /**
     * Buyer self-cancellation of a paid order. The refund/cancel window and the
     * post-check-in guard are enforced inside the domain service.
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        try {
            $refund = $this->events->cancelOrderByBuyer($order, $this->actingUser($request));
        } catch (SelfCancellationNotPermitted) {
            return $this->fail('This order can no longer be cancelled (outside the refund/cancellation window).', 422);
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage(), 422);
        }

        return $this->respond([
            'refund_id' => $refund->id,
            'order_id' => $order->id,
            'status' => $refund->status->value,
            'amount_minor' => $refund->amount_minor,
            'currency' => $refund->currency,
        ]);
    }

    /**
     * Check a ticket in at the door. Replay protection lives in the domain: a
     * non-issuable ticket yields a clean 409 rather than a bypass.
     */
    public function checkIn(Request $request, Ticket $ticket): JsonResponse
    {
        $event = $ticket->event()->firstOrFail();
        $this->authorize('checkIn', $event);

        try {
            $ticket = $this->events->checkIn($ticket, $this->actingUser($request));
        } catch (TicketNotCheckInable $e) {
            return $this->fail($e->getMessage(), 409);
        }

        return $this->respond(TicketResource::make($ticket));
    }

    /**
     * Build the holder assignment list reserve() expects. Explicit holders win;
     * otherwise the acting user is the sole holder (repeated for `quantity`).
     *
     * @param  array<string, mixed>  $data
     * @return array<int, array{name: string, email: string, user_id: int|string|null}>
     */
    private function resolveHolders(array $data, Model $user): array
    {
        if (isset($data['holders']) && is_array($data['holders']) && $data['holders'] !== []) {
            return array_values(array_map(static fn (array $holder): array => [
                'name' => (string) $holder['name'],
                'email' => (string) $holder['email'],
                'user_id' => null,
            ], $data['holders']));
        }

        $quantity = isset($data['quantity']) ? max(1, (int) $data['quantity']) : 1;

        $holder = [
            'name' => (string) ($user->getAttribute('name') ?? $user->getAttribute('email') ?? 'Attendee'),
            'email' => (string) ($user->getAttribute('email') ?? ''),
            'user_id' => $user->getKey(),
        ];

        return array_fill(0, $quantity, $holder);
    }
}
