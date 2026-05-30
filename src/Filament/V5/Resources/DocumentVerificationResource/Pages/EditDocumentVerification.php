<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Filament\V5\Resources\DocumentVerificationResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kurt\Modules\Events\Filament\V5\Resources\DocumentVerificationResource;

class EditDocumentVerification extends EditRecord
{
    protected static string $resource = DocumentVerificationResource::class;

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
