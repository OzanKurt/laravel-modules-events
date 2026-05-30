<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Filament\V4\Resources\OrderResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Kurt\Modules\Events\Filament\V4\Resources\OrderResource;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;
}
