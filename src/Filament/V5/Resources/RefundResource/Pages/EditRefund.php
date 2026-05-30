<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Filament\V5\Resources\RefundResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kurt\Modules\Events\Filament\V5\Resources\RefundResource;

class EditRefund extends EditRecord
{
    protected static string $resource = RefundResource::class;

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
