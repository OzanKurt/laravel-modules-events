<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Filament\V4;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Kurt\Modules\Events\Filament\V4\Resources\ApplicationResource;
use Kurt\Modules\Events\Filament\V4\Resources\DiscountCodeResource;
use Kurt\Modules\Events\Filament\V4\Resources\DocumentVerificationResource;
use Kurt\Modules\Events\Filament\V4\Resources\EventResource;
use Kurt\Modules\Events\Filament\V4\Resources\OrderResource;
use Kurt\Modules\Events\Filament\V4\Resources\RefundResource;
use Kurt\Modules\Events\Filament\V4\Resources\TicketTypeResource;
use Kurt\Modules\Events\Filament\V4\Resources\WaitlistResource;

final class EventsPlugin implements Plugin
{
    public function getId(): string
    {
        return 'kurtmodules-events';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            EventResource::class,
            TicketTypeResource::class,
            OrderResource::class,
            ApplicationResource::class,
            DiscountCodeResource::class,
            DocumentVerificationResource::class,
            RefundResource::class,
            WaitlistResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}

    public static function make(): static
    {
        /** @var static */
        return app(self::class);
    }
}
