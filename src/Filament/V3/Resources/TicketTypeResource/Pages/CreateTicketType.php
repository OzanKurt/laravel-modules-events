<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Filament\V3\Resources\TicketTypeResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kurt\Modules\Events\Filament\V3\Resources\TicketTypeResource;

class CreateTicketType extends CreateRecord
{
    protected static string $resource = TicketTypeResource::class;
}
