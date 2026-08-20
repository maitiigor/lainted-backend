<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Domain\Trust\Services\UserModerationService;
use App\Domain\Users\Models\User;
use App\Support\Enums\AccountStatus;
use App\Support\Enums\IssuedBy;
use App\Support\Enums\StrikeType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('uuid')
                    ->label('UUID')
                    ->required()
                    ->maxLength(36),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('google_id')
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->maxLength(255),
                Forms\Components\TextInput::make('photo_path')
                    ->maxLength(255),
                Forms\Components\Textarea::make('bio')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('landmark')
                    ->maxLength(255),
                Forms\Components\TextInput::make('seller_type')
                    ->required()
                    ->maxLength(255)
                    ->default('none'),
                Forms\Components\TextInput::make('account_status')
                    ->required()
                    ->maxLength(255)
                    ->default('active'),
                Forms\Components\DateTimePicker::make('suspended_until'),
                Forms\Components\Toggle::make('is_verified')
                    ->required(),
                Forms\Components\TextInput::make('verification_type')
                    ->maxLength(255),
                Forms\Components\TextInput::make('seller_rank')
                    ->required()
                    ->maxLength(255)
                    ->default('new'),
                Forms\Components\TextInput::make('preferred_language')
                    ->required()
                    ->maxLength(255)
                    ->default('en'),
                Forms\Components\DateTimePicker::make('accepted_seller_agreement_at'),
                Forms\Components\DateTimePicker::make('last_active_at'),
                Forms\Components\DateTimePicker::make('email_verified_at'),
                Forms\Components\Select::make('lga_id')
                    ->relationship('lga', 'name'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('google_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('photo_path')
                    ->searchable(),
                Tables\Columns\TextColumn::make('landmark')
                    ->searchable(),
                Tables\Columns\TextColumn::make('seller_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('account_status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('suspended_until')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_verified')
                    ->boolean(),
                Tables\Columns\TextColumn::make('verification_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('seller_rank')
                    ->searchable(),
                Tables\Columns\TextColumn::make('preferred_language')
                    ->searchable(),
                Tables\Columns\TextColumn::make('accepted_seller_agreement_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_active_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lga.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('issueStrike')
                    ->label('Issue strike')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => $record->account_status !== AccountStatus::Banned)
                    ->form([
                        Forms\Components\Select::make('type')
                            ->label('Reason type')
                            ->options(collect(StrikeType::cases())->mapWithKeys(fn ($c) => [$c->value => $c->name])->all())
                            ->required(),
                        Forms\Components\Textarea::make('reason')->required(),
                    ])
                    ->action(function (User $record, array $data) {
                        // First real use of IssuedBy::Admin - every strike
                        // issued until now has been System-issued.
                        app(UserModerationService::class)->issueStrike(
                            $record,
                            StrikeType::from($data['type']),
                            $data['reason'],
                            IssuedBy::Admin,
                            admin: auth()->user(),
                        );

                        Notification::make()->title('Strike issued.')->success()->send();
                    }),
                Tables\Actions\Action::make('warn')
                    ->label('Warn')
                    ->icon('heroicon-o-flag')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => $record->account_status === AccountStatus::Active)
                    ->action(function (User $record) {
                        app(UserModerationService::class)->warn($record);

                        Notification::make()->title('User warned.')->success()->send();
                    }),
                Tables\Actions\Action::make('suspend')
                    ->label('Suspend 7 days')
                    ->icon('heroicon-o-no-symbol')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => $record->account_status !== AccountStatus::Banned)
                    ->action(function (User $record) {
                        app(UserModerationService::class)->suspend($record);

                        Notification::make()->title('User suspended for 7 days.')->success()->send();
                    }),
                Tables\Actions\Action::make('ban')
                    ->label('Ban permanently')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => $record->account_status !== AccountStatus::Banned)
                    ->action(function (User $record) {
                        app(UserModerationService::class)->ban($record);

                        Notification::make()->title('User banned - wallet forfeited, ratings reset.')->success()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
