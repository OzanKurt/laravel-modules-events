<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Ticketing;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Kurt\Modules\Events\Ticketing\Models\ReferralLink;

/**
 * @extends Factory<ReferralLink>
 */
class ReferralLinkFactory extends Factory
{
    /** @var class-string<ReferralLink> */
    protected $model = ReferralLink::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organizer_id' => 1,
            'code' => Str::random(8),
            'commission_basis_points' => 500,
            'uses_count' => 0,
            'active' => true,
        ];
    }
}
