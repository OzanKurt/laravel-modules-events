<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Filament\V5\Resources\EventResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kurt\Modules\Events\Filament\V5\Resources\EventResource;

class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
