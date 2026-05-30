<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Filament\V5\Resources\TicketTypeResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kurt\Modules\Events\Filament\V5\Resources\TicketTypeResource;

class EditTicketType extends EditRecord
{
    protected static string $resource = TicketTypeResource::class;

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
