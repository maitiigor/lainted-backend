<?php

namespace App\Filament\Resources;

use App\Domain\Chat\Models\ContactLeakStrike;
use App\Domain\Trust\Services\UserModerationService;
use App\Filament\Resources\ContactLeakStrikeResource\Pages;
use App\Support\Enums\AccountStatus;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * List+view only, matching EscrowResource's system-managed philosophy -
 * these rows are written automatically by MessageService's contact-leak
 * detection. The suspend/ban actions here are a manual override, not the
 * only lever anymore: UserModerationService::issueStrike() now auto-
 * escalates account_status off strike count (see its own doc comment for
 * how the product doc's two contradictory strike-threshold passages were
 * reconciled). Both delegate to the same service so behavior stays
 * consistent regardless of which admin surface triggered it.
 */
class ContactLeakStrikeResource extends Resource
{
    protected static ?string $model = ContactLeakStrike::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationGroup = 'Trust & Safety';

    protected static ?string $navigationLabel = 'Contact Leak Log';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user.name')->label('User')->disabled(),
                Forms\Components\TextInput::make('detected_pattern')->disabled(),
                Forms\Components\Textarea::make('matched_text')->disabled()->columnSpanFull(),
                Forms\Components\TextInput::make('source_type')->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.account_status')
                    ->label('Status')
                    ->badge(),
                Tables\Columns\TextColumn::make('detected_pattern')
                    ->badge(),
                Tables\Columns\TextColumn::make('matched_text')
                    ->limit(40),
                Tables\Columns\TextColumn::make('source_type'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('suspend')
                    ->label('Suspend 7 days')
                    ->icon('heroicon-o-no-symbol')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (ContactLeakStrike $record) => $record->user->account_status !== AccountStatus::Banned)
                    ->action(function (ContactLeakStrike $record) {
                        app(UserModerationService::class)->suspend($record->user);

                        Notification::make()->title('User suspended for 7 days.')->success()->send();
                    }),
                Tables\Actions\Action::make('ban')
                    ->label('Ban permanently')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (ContactLeakStrike $record) => $record->user->account_status !== AccountStatus::Banned)
                    ->action(function (ContactLeakStrike $record) {
                        app(UserModerationService::class)->ban($record->user);

                        Notification::make()->title('User banned - wallet forfeited, ratings reset.')->success()->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactLeakStrikes::route('/'),
            'view' => Pages\ViewContactLeakStrike::route('/{record}'),
        ];
    }
}
