<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Ticketing;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Ticketing\Models\DiscountCode;
use Kurt\Modules\Events\Ticketing\Models\DiscountCodeUsage;
use Kurt\Modules\Events\Ticketing\Models\Order;

/**
 * @extends Factory<DiscountCodeUsage>
 */
class DiscountCodeUsageFactory extends Factory
{
    /** @var class-string<DiscountCodeUsage> */
    protected $model = DiscountCodeUsage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'discount_code_id' => DiscountCode::factory(),
            'order_id' => Order::factory(),
            'user_id' => 1,
            'applied_minor' => 100,
            'currency' => 'USD',
        ];
    }
}
