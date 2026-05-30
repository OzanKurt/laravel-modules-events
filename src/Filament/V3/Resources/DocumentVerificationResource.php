<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Filament\V3\Resources;

use Filament\Facades\Filament;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Events\Eligibility\Enums\VerificationStatus;
use Kurt\Modules\Events\Eligibility\Models\DocumentVerification;
use Kurt\Modules\Events\Filament\V3\Resources\DocumentVerificationResource\Pages;

class DocumentVerificationResource extends Resource
{
    protected static ?string $model = DocumentVerification::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'Events';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Textarea::make('note')
                            ->label('Review note')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('documentUpload.filename')
                    ->label('Document')
                    ->limit(40)
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('documentUpload.kind')
                    ->label('Kind')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (VerificationStatus $state): string => match ($state) {
                        VerificationStatus::Pending => 'warning',
                        VerificationStatus::Verified => 'success',
                        VerificationStatus::Rejected => 'danger',
                    })
                    ->sortable(),
                TextColumn::make('reviewer.name')
                    ->label('Reviewed by')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('decided_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(VerificationStatus::class)
                    ->default(VerificationStatus::Pending->value),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('verify')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (DocumentVerification $record): bool => $record->status !== VerificationStatus::Verified)
                    ->action(fn (DocumentVerification $record) => static::decide($record, VerificationStatus::Verified)),
                Action::make('reject')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->visible(fn (DocumentVerification $record): bool => $record->status !== VerificationStatus::Rejected)
                    ->form([
                        Textarea::make('note')
                            ->label('Rejection note')
                            ->rows(2),
                    ])
                    ->action(fn (DocumentVerification $record, array $data) => static::decide($record, VerificationStatus::Rejected, $data['note'] ?? null)),
            ]);
    }

    protected static function decide(DocumentVerification $record, VerificationStatus $status, ?string $note = null): void
    {
        $record->forceFill([
            'status' => $status,
            'decided_by' => static::actor()->getKey(),
            'decided_at' => now(),
            'note' => $note ?? $record->note,
        ])->save();
    }

    protected static function actor(): Model
    {
        $user = Filament::auth()->user();

        if (! $user instanceof Model) {
            throw new \RuntimeException('An authenticated reviewer is required to decide on a document.');
        }

        return $user;
    }

    /**
     * @return array<class-string, mixed>
     */
    public static function getRelations(): array
    {
        return [];
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocumentVerifications::route('/'),
            'edit' => Pages\EditDocumentVerification::route('/{record}/edit'),
        ];
    }
}
