<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Filament\V5\Resources;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
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
use Kurt\Modules\Events\Filament\V5\Resources\OrderResource\Pages;
use Kurt\Modules\Events\Flow\Enums\RefundReason;
use Kurt\Modules\Events\Support\Events;
use Kurt\Modules\Events\Ticketing\Enums\OrderStatus;
use Kurt\Modules\Events\Ticketing\Models\Order;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|\UnitEnum|null $navigationGroup = 'Events';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order')
                    ->schema([
                        Select::make('status')
                            ->options(OrderStatus::class)
                            ->disabled()
                            ->dehydrated(false),
                        Textarea::make('processor_reference')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(1),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('buyer.name')
                    ->label('Buyer')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('event.title')
                    ->label('Event')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (OrderStatus $state): string => match ($state) {
                        OrderStatus::Pending => 'warning',
                        OrderStatus::Paid => 'success',
                        OrderStatus::Cancelled => 'gray',
                        OrderStatus::Refunded => 'danger',
                        OrderStatus::PartiallyRefunded => 'info',
                    })
                    ->sortable(),
                TextColumn::make('total_minor')
                    ->label('Total')
                    ->money(fn (Order $record): string => $record->currency)
                    ->sortable(),
                TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(OrderStatus::class),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('requestRefund')
                    ->label('Request refund')
                    ->icon(Heroicon::ArrowUturnLeft)
                    ->color('danger')
                    ->visible(fn (Order $record): bool => $record->status === OrderStatus::Paid)
                    ->form([
                        Textarea::make('note')
                            ->label('Reason note')
                            ->rows(2),
                    ])
                    ->action(function (Order $record, array $data): void {
                        app(Events::class)->requestRefund(
                            $record,
                            static::actor(),
                            RefundReason::OrganizerInitiated,
                            $data['note'] ?? null,
                        );
                    }),
            ]);
    }

    protected static function actor(): Model
    {
        $user = Filament::auth()->user();

        if (! $user instanceof Model) {
            throw new \RuntimeException('An authenticated user is required to request a refund.');
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
            'index' => Pages\ListOrders::route('/'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
