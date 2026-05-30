<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Filament\V5\Resources;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Events\Eligibility\Enums\VerificationStatus;
use Kurt\Modules\Events\Eligibility\Models\DocumentVerification;
use Kurt\Modules\Events\Filament\V5\Resources\DocumentVerificationResource\Pages;

class DocumentVerificationResource extends Resource
{
    protected static ?string $model = DocumentVerification::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Events';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                    ->icon(Heroicon::Check)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (DocumentVerification $record): bool => $record->status !== VerificationStatus::Verified)
                    ->action(fn (DocumentVerification $record) => static::decide($record, VerificationStatus::Verified)),
                Action::make('reject')
                    ->icon(Heroicon::XMark)
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
