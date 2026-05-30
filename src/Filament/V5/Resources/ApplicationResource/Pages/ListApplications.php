<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Filament\V5\Resources\ApplicationResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Kurt\Modules\Events\Filament\V5\Resources\ApplicationResource;

class ListApplications extends ListRecords
{
    protected static string $resource = ApplicationResource::class;
}
