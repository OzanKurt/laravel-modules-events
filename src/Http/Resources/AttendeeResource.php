<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Kurt\Modules\Events\Attendance\Models\Attendee;

/**
 * @mixin Attendee
 */
final class AttendeeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Attendee $attendee */
        $attendee = $this->resource;

        return [
            'id' => $attendee->id,
            'event_id' => $attendee->event_id,
            'ticket_id' => $attendee->ticket_id,
            'user_id' => $attendee->user_id,
            'status' => $attendee->status->value,
            'created_at' => $attendee->created_at?->toIso8601String(),
        ];
    }
}
