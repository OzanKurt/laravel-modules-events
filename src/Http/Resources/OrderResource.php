<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Kurt\Modules\Events\Ticketing\Models\Order;

/**
 * @mixin Order
 */
final class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Order $order */
        $order = $this->resource;

        return [
            'id' => $order->id,
            'event_id' => $order->event_id,
            'user_id' => $order->user_id,
            'status' => $order->status->value,
            'subtotal_minor' => $order->subtotal_minor,
            'discount_minor' => $order->discount_minor,
            'tax_minor' => $order->tax_minor,
            'total_minor' => $order->total_minor,
            'currency' => $order->currency,
            'paid_at' => $order->paid_at?->toIso8601String(),
            'created_at' => $order->created_at?->toIso8601String(),
            'tickets' => TicketResource::collection($this->whenLoaded('tickets')),
        ];
    }
}
