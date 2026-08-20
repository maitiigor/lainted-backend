<?php

namespace App\Filament\Resources;

use App\Domain\Trust\Models\Strike;
use App\Filament\Resources\StrikeResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * List+view only, mirroring ContactLeakStrikeResource's system-managed
 * philosophy - rows are written by UserModerationService::issueStrike(),
 * never hand-created here. This is the one place admins see every
 * strike, not just contact-leak ones (ContactLeakStrikeResource only
 * ever shows that one detail table's rows).
 */
class StrikeResource extends Resource
{
    protected static ?string $model = Strike::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationGroup = 'Trust & Safety';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user.name')->label('User')->disabled(),
                Forms\Components\TextInput::make('type')->disabled(),
                Forms\Components\Textarea::make('reason')->disabled()->columnSpanFull(),
                Forms\Components\TextInput::make('issued_by')->disabled(),
                Forms\Components\TextInput::make('admin.name')->label('Issued by (admin)')->disabled(),
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
                    ->label('Current status')
                    ->badge(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('reason')
                    ->limit(50),
                Tables\Columns\TextColumn::make('issued_by')
                    ->badge(),
                Tables\Columns\TextColumn::make('admin.name')
                    ->label('Admin'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStrikes::route('/'),
            'view' => Pages\ViewStrike::route('/{record}'),
        ];
    }
}
