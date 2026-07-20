<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Kurt\Modules\Events\Catalog\Models\Event;

/**
 * @mixin Event
 */
final class EventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Event $event */
        $event = $this->resource;

        return [
            'id' => $event->id,
            'slug' => $event->slug,
            'title' => $event->title,
            'description' => $event->description,
            'status' => $event->status->value,
            'visibility' => $event->visibility->value,
            'category_id' => $event->category_id,
            'starts_at' => $event->starts_at->toIso8601String(),
            'ends_at' => $event->ends_at->toIso8601String(),
            'timezone' => $event->timezone,
            'location_name' => $event->location_name,
            'location_address' => $event->location_address,
            'capacity' => $event->capacity,
            'tickets_sold_count' => $event->tickets_sold_count,
            'sale_starts_at' => $event->sale_starts_at?->toIso8601String(),
            'sale_ends_at' => $event->sale_ends_at?->toIso8601String(),
            'cancelled_at' => $event->cancelled_at?->toIso8601String(),
            'created_at' => $event->created_at?->toIso8601String(),
            'updated_at' => $event->updated_at?->toIso8601String(),
            'ticket_types' => TicketTypeResource::collection($this->whenLoaded('ticketTypes')),
        ];
    }
}
