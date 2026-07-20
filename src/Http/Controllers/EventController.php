<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kurt\Modules\Core\Http\Concerns\HandlesApiQuery;
use Kurt\Modules\Core\Http\Controllers\ApiController;
use Kurt\Modules\Events\Attendance\Models\Attendee;
use Kurt\Modules\Events\Catalog\Enums\EventStatus;
use Kurt\Modules\Events\Catalog\Enums\EventVisibility;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Http\Concerns\ResolvesActingUser;
use Kurt\Modules\Events\Http\Resources\AttendeeResource;
use Kurt\Modules\Events\Http\Resources\EventResource;
use Kurt\Modules\Events\Support\Events as EventsService;

/**
 * REST surface for events. Reads are public but scoped to published/public
 * events; writes require auth and the EventPolicy. The controller stays thin
 * over the Support\Events domain service.
 */
final class EventController extends ApiController
{
    use HandlesApiQuery;
    use ResolvesActingUser;

    public function __construct(private readonly EventsService $events) {}

    /**
     * Public listing of published, public events with sort/filter/pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Event::query()
            ->where('status', EventStatus::Published->value)
            ->where('visibility', EventVisibility::Public->value)
            ->whereNull('cancelled_at');

        $query = $this->applyApiFilters($query, $request, [
            'category_id' => 'exact',
            'timezone' => 'exact',
        ]);
        $query = $this->applyApiSorts($query, $request, ['starts_at', 'created_at']);

        return $this->respondPaginated($this->apiPaginate($query, $request), EventResource::class);
    }

    public function show(Request $request, Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        return $this->respond(EventResource::make($event->load('ticketTypes')));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validate($request, [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'category_id' => ['sometimes', 'nullable', 'integer'],
            'visibility' => ['sometimes', 'string', 'in:public,unlisted,private'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'timezone' => ['required', 'string', 'max:64'],
            'location_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'location_address' => ['sometimes', 'nullable', 'string'],
            'capacity' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ]);

        $event = $this->events->createEvent($data, $this->actingUser($request));

        return $this->respondCreated(EventResource::make($event));
    }

    public function update(Request $request, Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        $data = $this->validate($request, [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'category_id' => ['sometimes', 'nullable', 'integer'],
            'visibility' => ['sometimes', 'string', 'in:public,unlisted,private'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date'],
            'timezone' => ['sometimes', 'string', 'max:64'],
            'location_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'location_address' => ['sometimes', 'nullable', 'string'],
            'capacity' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ]);

        $event->update($data);

        return $this->respond(EventResource::make($event->refresh()));
    }

    public function destroy(Request $request, Event $event): JsonResponse
    {
        $this->authorize('delete', $event);

        $event->delete();

        return $this->respondNoContent();
    }

    /**
     * Transition an event to Published. Owner/Manager (EventPolicy::update) only.
     */
    public function publish(Request $request, Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        $this->events->publish($event);

        return $this->respond(EventResource::make($event->refresh()));
    }

    /**
     * Cancel an event with a reason. Owner/Manager (EventPolicy::update) only.
     */
    public function cancel(Request $request, Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        $data = $this->validate($request, [
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $this->events->cancel($event, $this->actingUser($request), $data['reason']);

        return $this->respond(EventResource::make($event->refresh()));
    }

    /**
     * Organizer view of an event's attendee roster.
     */
    public function attendees(Request $request, Event $event): JsonResponse
    {
        $this->authorize('viewAttendees', $event);

        $query = Attendee::query()->where('event_id', $event->id);

        return $this->respondPaginated($this->apiPaginate($query, $request), AttendeeResource::class);
    }
}
