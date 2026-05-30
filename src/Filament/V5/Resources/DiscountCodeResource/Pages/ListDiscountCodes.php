<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Filament\V5\Resources\DiscountCodeResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kurt\Modules\Events\Filament\V5\Resources\DiscountCodeResource;

class ListDiscountCodes extends ListRecords
{
    protected static string $resource = DiscountCodeResource::class;

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
