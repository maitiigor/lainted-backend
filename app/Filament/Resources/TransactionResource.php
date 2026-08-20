<?php

namespace App\Filament\Resources;

use App\Domain\Payments\Models\Transaction;
use App\Filament\Resources\TransactionResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * List+view only, no create/edit - see EscrowResource's doc comment, same
 * reasoning: system-managed rows, support-lookup only.
 */
class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Payments';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user.name')->label('User')->disabled(),
                Forms\Components\TextInput::make('order.uuid')->label('Order')->disabled(),
                Forms\Components\TextInput::make('type')->disabled(),
                Forms\Components\TextInput::make('amount')->disabled(),
                Forms\Components\TextInput::make('gateway')->disabled(),
                Forms\Components\TextInput::make('gateway_reference')->disabled(),
                Forms\Components\TextInput::make('status')->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable(),
                Tables\Columns\TextColumn::make('order.uuid')
                    ->label('Order')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('NGN')
                    ->sortable(),
                Tables\Columns\TextColumn::make('gateway'),
                Tables\Columns\TextColumn::make('gateway_reference')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),
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
            'index' => Pages\ListTransactions::route('/'),
            'view' => Pages\ViewTransaction::route('/{record}'),
        ];
    }
}
