<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Filament\V5\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Kurt\Modules\Events\Filament\V5\Resources\RefundResource\Pages;
use Kurt\Modules\Events\Flow\Enums\RefundReason;
use Kurt\Modules\Events\Flow\Enums\RefundStatus;
use Kurt\Modules\Events\Flow\Models\Refund;
use Kurt\Modules\Events\Support\Events;

class RefundResource extends Resource
{
    protected static ?string $model = Refund::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Events';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Textarea::make('reason_note')
                            ->label('Reason note')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('order.id')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('amount_minor')
                    ->label('Amount')
                    ->money(fn (Refund $record): string => $record->currency)
                    ->sortable(),
                TextColumn::make('reason')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (RefundStatus $state): string => match ($state) {
                        RefundStatus::Pending => 'warning',
                        RefundStatus::Processed => 'success',
                        RefundStatus::Failed => 'danger',
                    })
                    ->sortable(),
                TextColumn::make('processed_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(RefundStatus::class)
                    ->default(RefundStatus::Pending->value),
                SelectFilter::make('reason')
                    ->options(RefundReason::class),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('markProcessed')
                    ->label('Mark processed')
                    ->icon(Heroicon::Check)
                    ->color('success')
                    ->visible(fn (Refund $record): bool => $record->status === RefundStatus::Pending)
                    ->form([
                        TextInput::make('processor_reference')
                            ->label('Processor reference')
                            ->required(),
                    ])
                    ->action(fn (Refund $record, array $data) => app(Events::class)->markRefundProcessed($record, $data['processor_reference'])),
                Action::make('markFailed')
                    ->label('Mark failed')
                    ->icon(Heroicon::XMark)
                    ->color('danger')
                    ->visible(fn (Refund $record): bool => $record->status === RefundStatus::Pending)
                    ->form([
                        Textarea::make('note')
                            ->label('Failure note')
                            ->required()
                            ->rows(2),
                    ])
                    ->action(fn (Refund $record, array $data) => app(Events::class)->markRefundFailed($record, $data['note'])),
            ]);
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
            'index' => Pages\ListRefunds::route('/'),
            'edit' => Pages\EditRefund::route('/{record}/edit'),
        ];
    }
}
