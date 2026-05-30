<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Filament\V5\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
use Kurt\Modules\Events\Catalog\Enums\EventStatus;
use Kurt\Modules\Events\Catalog\Enums\EventVisibility;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Filament\V5\Resources\EventResource\Pages;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Events';

    protected static ?string $recordTitleAttribute = 'title';

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
                                        TextInput::make("title.{$locale}")
                                            ->label('Title')
                                            ->required($locale === 'en')
                                            ->maxLength(255),
                                        Textarea::make("description.{$locale}")
                                            ->label('Description')
                                            ->rows(4),
                                    ]),
                                static::$locales,
                            ))
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('Details')
                    ->schema([
                        Select::make('status')
                            ->options(EventStatus::class)
                            ->default(EventStatus::Draft)
                            ->required(),
                        Select::make('visibility')
                            ->options(EventVisibility::class)
                            ->default(EventVisibility::Private)
                            ->required(),
                        Select::make('category_id')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Category'),
                        TextInput::make('timezone')
                            ->default('UTC')
                            ->maxLength(64),
                        DateTimePicker::make('starts_at')
                            ->seconds(false)
                            ->required(),
                        DateTimePicker::make('ends_at')
                            ->seconds(false)
                            ->required(),
                        TextInput::make('capacity')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Leave empty for unlimited.'),
                    ])
                    ->columns(2),

                Section::make('Sales window')
                    ->schema([
                        DateTimePicker::make('sale_starts_at')
                            ->seconds(false),
                        DateTimePicker::make('sale_ends_at')
                            ->seconds(false),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (EventStatus $state): string => match ($state) {
                        EventStatus::Draft => 'gray',
                        EventStatus::PendingApproval => 'warning',
                        EventStatus::Published => 'success',
                        EventStatus::Cancelled => 'danger',
                        EventStatus::Completed => 'info',
                    })
                    ->sortable(),
                TextColumn::make('visibility')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->toggleable(),
                TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('capacity')
                    ->numeric()
                    ->placeholder('∞')
                    ->toggleable(),
                TextColumn::make('tickets_sold_count')
                    ->label('Sold')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(EventStatus::class),
                SelectFilter::make('visibility')
                    ->options(EventVisibility::class),
            ])
            ->defaultSort('starts_at', 'desc')
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
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
