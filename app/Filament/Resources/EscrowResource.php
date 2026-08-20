<?php

namespace App\Filament\Resources;

use App\Domain\Payments\Models\Escrow;
use App\Filament\Resources\EscrowResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * List+view only, no create/edit - escrow rows are system-managed (held
 * at order-payment-confirmation, released only by Phase 7's job). This
 * exists purely so support staff can look up "did this payment actually
 * get held," not to let anyone hand-edit a ledger row.
 */
class EscrowResource extends Resource
{
    protected static ?string $model = Escrow::class;

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationGroup = 'Payments';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('order.uuid')->label('Order')->disabled(),
                Forms\Components\TextInput::make('amount')->disabled(),
                Forms\Components\TextInput::make('status')->disabled(),
                Forms\Components\DateTimePicker::make('held_at')->disabled(),
                Forms\Components\DateTimePicker::make('released_at')->disabled(),
                Forms\Components\DateTimePicker::make('refunded_at')->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.uuid')
                    ->label('Order')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('NGN')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('held_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('released_at')
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
            'index' => Pages\ListEscrows::route('/'),
            'view' => Pages\ViewEscrow::route('/{record}'),
        ];
    }
}
