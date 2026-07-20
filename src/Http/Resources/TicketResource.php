<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Kurt\Modules\Events\Ticketing\Models\Ticket;

/**
 * @mixin Ticket
 */
final class TicketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Ticket $ticket */
        $ticket = $this->resource;

        return [
            'id' => $ticket->id,
            'event_id' => $ticket->event_id,
            'ticket_type_id' => $ticket->ticket_type_id,
            'order_item_id' => $ticket->order_item_id,
            'holder_id' => $ticket->holder_id,
            'holder_name' => $ticket->holder_name,
            'holder_email' => $ticket->holder_email,
            'status' => $ticket->status->value,
            'checked_in_at' => $ticket->checked_in_at?->toIso8601String(),
            'created_at' => $ticket->created_at?->toIso8601String(),
        ];
    }
}
