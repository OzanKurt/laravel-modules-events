<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

/**
 * @mixin TicketType
 */
final class TicketTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var TicketType $type */
        $type = $this->resource;

        return [
            'id' => $type->id,
            'event_id' => $type->event_id,
            'slug' => $type->slug,
            'name' => $type->name,
            'description' => $type->description,
            'mode' => $type->mode->value,
            'price_minor' => $type->price_minor,
            'currency' => $type->currency,
            'refundable' => $type->refundable,
            'transferable' => $type->transferable,
            'capacity' => $type->capacity,
            'sold_count' => $type->sold_count,
            'max_per_order' => $type->max_per_order,
            'sale_starts_at' => $type->sale_starts_at?->toIso8601String(),
            'sale_ends_at' => $type->sale_ends_at?->toIso8601String(),
            'position' => $type->position,
        ];
    }
}
