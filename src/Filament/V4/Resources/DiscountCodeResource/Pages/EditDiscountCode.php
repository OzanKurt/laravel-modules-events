<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Filament\V4\Resources\DiscountCodeResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kurt\Modules\Events\Filament\V4\Resources\DiscountCodeResource;

class EditDiscountCode extends EditRecord
{
    protected static string $resource = DiscountCodeResource::class;

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
