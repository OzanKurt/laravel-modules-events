<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Filament\V5\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Kurt\Modules\Events\Filament\V5\Resources\DiscountCodeResource\Pages;
use Kurt\Modules\Events\Ticketing\Enums\DiscountApplicationScope;
use Kurt\Modules\Events\Ticketing\Enums\DiscountKind;
use Kurt\Modules\Events\Ticketing\Enums\DiscountScope;
use Kurt\Modules\Events\Ticketing\Models\DiscountCode;

class DiscountCodeResource extends Resource
{
    protected static ?string $model = DiscountCode::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|\UnitEnum|null $navigationGroup = 'Events';

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Code')
                    ->schema([
                        TextInput::make('code')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('description')
                            ->maxLength(255),
                        Select::make('kind')
                            ->options(DiscountKind::class)
                            ->default(DiscountKind::Percent)
                            ->required(),
                        Select::make('application_scope')
                            ->options(DiscountApplicationScope::class)
                            ->default(DiscountApplicationScope::Order)
                            ->required(),
                        Select::make('applies_to')
                            ->options(DiscountScope::class)
                            ->default(DiscountScope::Global)
                            ->required(),
                        TextInput::make('amount_minor')
                            ->label('Amount')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->helperText('Percent: basis points (1000 = 10.00%). Flat: minor units.'),
                        TextInput::make('currency')
                            ->maxLength(3)
                            ->helperText('Required for flat-amount codes.'),
                    ])
                    ->columns(2),

                Section::make('Limits')
                    ->schema([
                        TextInput::make('max_uses_total')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('max_uses_per_user')
                            ->numeric()
                            ->minValue(0),
                        DateTimePicker::make('starts_at')
                            ->seconds(false),
                        DateTimePicker::make('expires_at')
                            ->seconds(false),
                        Toggle::make('active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kind')
                    ->badge()
                    ->color(fn (DiscountKind $state): string => match ($state) {
                        DiscountKind::Percent => 'info',
                        DiscountKind::FlatAmount => 'success',
                    }),
                TextColumn::make('amount_minor')
                    ->label('Amount')
                    ->numeric(),
                TextColumn::make('uses_count')
                    ->label('Uses')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('kind')
                    ->options(DiscountKind::class),
                SelectFilter::make('active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListDiscountCodes::route('/'),
            'create' => Pages\CreateDiscountCode::route('/create'),
            'edit' => Pages\EditDiscountCode::route('/{record}/edit'),
        ];
    }
}
