<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Filament\V5\Resources\EventResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kurt\Modules\Events\Filament\V5\Resources\EventResource;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;
}
