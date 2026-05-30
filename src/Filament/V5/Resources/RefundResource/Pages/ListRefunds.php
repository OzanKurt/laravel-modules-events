<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Filament\V5\Resources\RefundResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Kurt\Modules\Events\Filament\V5\Resources\RefundResource;

class ListRefunds extends ListRecords
{
    protected static string $resource = RefundResource::class;
}
