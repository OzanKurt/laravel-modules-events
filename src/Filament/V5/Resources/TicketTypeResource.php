<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Filament\V5\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Kurt\Modules\Events\Filament\V5\Resources\TicketTypeResource\Pages;
use Kurt\Modules\Events\Ticketing\Enums\TicketTypeMode;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

class TicketTypeResource extends Resource
{
    protected static ?string $model = TicketType::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|\UnitEnum|null $navigationGroup = 'Events';

    protected static ?string $recordTitleAttribute = 'name';

    /** @var array<int, string> */
    protected static array $locales = ['en', 'tr'];

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Tabs::make('translations')
                            ->tabs(array_map(
                                fn (string $locale): Tab => Tab::make(strtoupper($locale))
                                    ->schema([
                                        TextInput::make("name.{$locale}")
                                            ->label('Name')
                                            ->required($locale === 'en')
                                            ->maxLength(255),
                                        Textarea::make("description.{$locale}")
                                            ->label('Description')
                                            ->rows(3),
                                    ]),
                                static::$locales,
                            ))
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('Details')
                    ->schema([
                        Select::make('event_id')
                            ->relationship('event', 'title')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Event'),
                        Select::make('mode')
                            ->options(TicketTypeMode::class)
                            ->default(TicketTypeMode::Open)
                            ->required(),
                        TextInput::make('price_minor')
                            ->label('Price (minor units)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->helperText('Stored in cents, e.g. 1000 = 10.00.'),
                        TextInput::make('currency')
                            ->default('USD')
                            ->required()
                            ->maxLength(3),
                        TextInput::make('capacity')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Leave empty for unlimited.'),
                        TextInput::make('max_per_order')
                            ->numeric()
                            ->minValue(1)
                            ->default(10),
                        Toggle::make('refundable')
                            ->default(true),
                        Toggle::make('transferable')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('event.title')
                    ->label('Event')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('mode')
                    ->badge()
                    ->color(fn (TicketTypeMode $state): string => match ($state) {
                        TicketTypeMode::Open => 'success',
                        TicketTypeMode::Application => 'warning',
                        TicketTypeMode::Rsvp => 'info',
                    }),
                TextColumn::make('price_minor')
                    ->label('Price')
                    ->money(fn (TicketType $record): string => $record->currency)
                    ->sortable(),
                TextColumn::make('capacity')
                    ->numeric()
                    ->placeholder('∞'),
                TextColumn::make('sold_count')
                    ->label('Sold')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('mode')
                    ->options(TicketTypeMode::class),
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
            'index' => Pages\ListTicketTypes::route('/'),
            'create' => Pages\CreateTicketType::route('/create'),
            'edit' => Pages\EditTicketType::route('/{record}/edit'),
        ];
    }
}
