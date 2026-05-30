<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Filament\V3\Resources;

use Filament\Forms\Form;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Kurt\Modules\Events\Filament\V3\Resources\WaitlistResource\Pages;
use Kurt\Modules\Events\Flow\Enums\WaitlistStatus;
use Kurt\Modules\Events\Flow\Models\WaitlistEntry;

class WaitlistResource extends Resource
{
    protected static ?string $model = WaitlistEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationGroup = 'Events';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ticketType.name')
                    ->label('Ticket type')
                    ->limit(40)
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (WaitlistStatus $state): string => match ($state) {
                        WaitlistStatus::Waiting => 'warning',
                        WaitlistStatus::Offered => 'info',
                        WaitlistStatus::Claimed => 'success',
                        WaitlistStatus::Expired => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('offered_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(WaitlistStatus::class),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListWaitlistEntries::route('/'),
        ];
    }
}
