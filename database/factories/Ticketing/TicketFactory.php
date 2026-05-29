<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Ticketing;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Ticketing\Enums\TicketStatus;
use Kurt\Modules\Events\Ticketing\Models\OrderItem;
use Kurt\Modules\Events\Ticketing\Models\Ticket;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /** @var class-string<Ticket> */
    protected $model = Ticket::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = TicketType::factory()->create();

        return [
            'order_item_id' => OrderItem::factory()->state(['ticket_type_id' => $type->id]),
            'ticket_type_id' => $type->id,
            'event_id' => Event::factory(),
            'holder_name' => $this->faker->name(),
            'holder_email' => $this->faker->safeEmail(),
            'status' => TicketStatus::Issued,
            'qr_token' => Str::random(40),
        ];
    }

    public function checkedIn(): static
    {
        return $this->state(fn () => [
            'status' => TicketStatus::CheckedIn,
            'checked_in_at' => now(),
        ]);
    }
}
