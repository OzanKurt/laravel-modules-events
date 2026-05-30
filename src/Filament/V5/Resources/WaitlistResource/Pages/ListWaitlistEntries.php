<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Filament\V5\Resources\WaitlistResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Kurt\Modules\Events\Filament\V5\Resources\WaitlistResource;

class ListWaitlistEntries extends ListRecords
{
    protected static string $resource = WaitlistResource::class;
}
