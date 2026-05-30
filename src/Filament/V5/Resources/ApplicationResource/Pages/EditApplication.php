<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Filament\V5\Resources\ApplicationResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kurt\Modules\Events\Filament\V5\Resources\ApplicationResource;

class EditApplication extends EditRecord
{
    protected static string $resource = ApplicationResource::class;

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
