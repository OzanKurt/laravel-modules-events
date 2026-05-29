<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Ticketing;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Catalog\Models\Session;
use Kurt\Modules\Events\Ticketing\Models\SessionCheckIn;
use Kurt\Modules\Events\Ticketing\Models\Ticket;

/**
 * @extends Factory<SessionCheckIn>
 */
class SessionCheckInFactory extends Factory
{
    /** @var class-string<SessionCheckIn> */
    protected $model = SessionCheckIn::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'session_id' => Session::factory(),
            'ticket_id' => Ticket::factory(),
            'checked_in_at' => now(),
        ];
    }
}
