<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Ticketing;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Kurt\Modules\Events\Ticketing\Enums\DiscountApplicationScope;
use Kurt\Modules\Events\Ticketing\Enums\DiscountKind;
use Kurt\Modules\Events\Ticketing\Enums\DiscountScope;
use Kurt\Modules\Events\Ticketing\Models\DiscountCode;

/**
 * @extends Factory<DiscountCode>
 */
class DiscountCodeFactory extends Factory
{
    /** @var class-string<DiscountCode> */
    protected $model = DiscountCode::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(8)),
            'kind' => DiscountKind::Percent,
            'amount_minor' => 1000,
            'application_scope' => DiscountApplicationScope::Order,
            'applies_to' => DiscountScope::Global,
            'uses_count' => 0,
            'active' => true,
        ];
    }

    public function flatAmount(int $amount, string $currency = 'USD'): static
    {
        return $this->state(fn () => [
            'kind' => DiscountKind::FlatAmount,
            'amount_minor' => $amount,
            'currency' => $currency,
        ]);
    }

    public function scopedToEventsSubset(): static
    {
        return $this->state(fn () => ['applies_to' => DiscountScope::EventsSubset]);
    }
}
