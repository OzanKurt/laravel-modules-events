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
use Kurt\Modules\Events\Attendance\Enums\ApplicationStatus;
use Kurt\Modules\Events\Attendance\Models\Application;
use Kurt\Modules\Events\Filament\V5\Resources\ApplicationResource\Pages;
use Kurt\Modules\Events\Support\Events;

class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|\UnitEnum|null $navigationGroup = 'Events';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Textarea::make('decision_note')
                            ->label('Decision note')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('applicant.name')
                    ->label('Applicant')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('event.title')
                    ->label('Event')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('ticketType.name')
                    ->label('Ticket type')
                    ->limit(30)
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (ApplicationStatus $state): string => match ($state) {
                        ApplicationStatus::Pending => 'warning',
                        ApplicationStatus::Approved => 'success',
                        ApplicationStatus::Rejected => 'danger',
                        ApplicationStatus::Withdrawn => 'gray',
                        ApplicationStatus::Expired => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ApplicationStatus::class)
                    ->default(ApplicationStatus::Pending->value),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->actions([
                Action::make('approve')
                    ->icon(Heroicon::Check)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Application $record): bool => $record->status === ApplicationStatus::Pending)
                    ->action(fn (Application $record) => app(Events::class)->approve($record, static::actor())),
                Action::make('reject')
                    ->icon(Heroicon::XMark)
                    ->color('danger')
                    ->visible(fn (Application $record): bool => $record->status === ApplicationStatus::Pending)
                    ->form([
                        Textarea::make('reason')
                            ->label('Rejection reason')
                            ->required()
                            ->rows(2),
                    ])
                    ->action(fn (Application $record, array $data) => app(Events::class)->reject($record, static::actor(), $data['reason'])),
            ]);
    }

    protected static function actor(): Model
    {
        $user = Filament::auth()->user();

        if (! $user instanceof Model) {
            throw new \RuntimeException('An authenticated user is required to decide on an application.');
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
            'index' => Pages\ListApplications::route('/'),
            'edit' => Pages\EditApplication::route('/{record}/edit'),
        ];
    }
}
