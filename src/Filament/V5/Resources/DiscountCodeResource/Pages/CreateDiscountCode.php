<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Filament\V5\Resources\DiscountCodeResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kurt\Modules\Events\Filament\V5\Resources\DiscountCodeResource;

class CreateDiscountCode extends CreateRecord
{
    protected static string $resource = DiscountCodeResource::class;
}
