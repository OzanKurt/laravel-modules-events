<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kurt\Modules\Core\Http\Concerns\HandlesApiQuery;
use Kurt\Modules\Core\Http\Controllers\ApiController;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Http\Resources\TicketTypeResource;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

/**
 * CRUD for an event's ticket types. Listing is public (gated by the event's
 * visibility); create/update/delete require the TicketTypePolicy (organizer).
 */
final class TicketTypeController extends ApiController
{
    use HandlesApiQuery;

    /**
     * Ticket types for a single event.
     */
    public function index(Request $request, Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        $query = $event->ticketTypes()->getQuery();
        $query = $this->applyApiSorts($query, $request, ['position', 'price_minor', 'created_at']);

        return $this->respondPaginated($this->apiPaginate($query, $request), TicketTypeResource::class);
    }

    public function store(Request $request, Event $event): JsonResponse
    {
        $this->authorize('create', [TicketType::class, $event]);

        $data = $this->validate($request, [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'mode' => ['sometimes', 'string', 'in:open,application,rsvp'],
            'price_minor' => ['required', 'integer', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'capacity' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'max_per_order' => ['sometimes', 'integer', 'min:1'],
            'refundable' => ['sometimes', 'boolean'],
            'transferable' => ['sometimes', 'boolean'],
        ]);

        $data['currency'] ??= (string) config('events.currency', 'USD');
        $data['mode'] ??= 'open';

        /** @var TicketType $type */
        $type = $event->ticketTypes()->create($data);

        return $this->respondCreated(TicketTypeResource::make($type));
    }

    public function update(Request $request, TicketType $ticketType): JsonResponse
    {
        $this->authorize('update', $ticketType);

        $data = $this->validate($request, [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'mode' => ['sometimes', 'string', 'in:open,application,rsvp'],
            'price_minor' => ['sometimes', 'integer', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'capacity' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'max_per_order' => ['sometimes', 'integer', 'min:1'],
            'refundable' => ['sometimes', 'boolean'],
            'transferable' => ['sometimes', 'boolean'],
        ]);

        $ticketType->update($data);

        return $this->respond(TicketTypeResource::make($ticketType->refresh()));
    }

    public function destroy(Request $request, TicketType $ticketType): JsonResponse
    {
        $this->authorize('delete', $ticketType);

        $ticketType->delete();

        return $this->respondNoContent();
    }
}
