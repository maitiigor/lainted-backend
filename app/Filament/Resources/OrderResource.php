<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Domain\Orders\Models\Order;
use App\Support\Enums\DeliveryTier;
use App\Support\Enums\OrderStatus;
use App\Support\Enums\PaymentMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('uuid')
                    ->label('UUID')
                    ->required()
                    ->maxLength(36),
                Forms\Components\Select::make('listing_id')
                    ->relationship('listing', 'title'),
                Forms\Components\Select::make('bundle_id')
                    ->relationship('bundle', 'title'),
                Forms\Components\Select::make('offer_id')
                    ->relationship('offer', 'id'),
                Forms\Components\Select::make('delivery_address_id')
                    ->relationship('deliveryAddress', 'id')
                    ->required(),
                Forms\Components\Select::make('discount_code_id')
                    ->relationship('discountCode', 'id'),
                Forms\Components\TextInput::make('discount_amount')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\Select::make('buyer_id')
                    ->relationship('buyer', 'name')
                    ->required(),
                Forms\Components\Select::make('seller_id')
                    ->relationship('seller', 'name')
                    ->required(),
                Forms\Components\Select::make('delivery_tier')
                    ->options(collect(DeliveryTier::cases())->mapWithKeys(fn ($c) => [$c->value => $c->name])->all())
                    ->required(),
                Forms\Components\Select::make('payment_method')
                    ->options(collect(PaymentMethod::cases())->mapWithKeys(fn ($c) => [$c->value => $c->name])->all())
                    ->required()
                    ->default('online'),
                Forms\Components\TextInput::make('logistics_provider')
                    ->maxLength(255),
                Forms\Components\TextInput::make('tracking_reference')
                    ->maxLength(255),
                Forms\Components\TextInput::make('item_price')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('commission_rate')
                    ->numeric(),
                Forms\Components\TextInput::make('commission_amount')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('protection_fee')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('delivery_fee')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('total_amount')
                    ->required()
                    ->numeric(),
                Forms\Components\Select::make('status')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->name])->all())
                    ->required()
                    ->default('pending'),
                Forms\Components\DateTimePicker::make('delivered_at'),
                Forms\Components\DateTimePicker::make('dispute_deadline_at'),
                Forms\Components\DateTimePicker::make('completed_at'),
                Forms\Components\TextInput::make('pickup_code')
                    ->maxLength(4),
                Forms\Components\DateTimePicker::make('pickup_code_entered_at'),
                Forms\Components\TextInput::make('package_photo_url')
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('buyer_acknowledged_at'),
                Forms\Components\TextInput::make('delivery_photo_url')
                    ->maxLength(255),
                Forms\Components\Toggle::make('delivery_confirmed_code')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('listing.title')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bundle.title')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('offer.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('deliveryAddress.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('discountCode.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('buyer.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('seller.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivery_tier')
                    ->searchable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->searchable(),
                Tables\Columns\TextColumn::make('logistics_provider')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tracking_reference')
                    ->searchable(),
                Tables\Columns\TextColumn::make('item_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('commission_rate')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('commission_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('protection_fee')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivery_fee')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('escrow.status')
                    ->label('Escrow'),
                Tables\Columns\TextColumn::make('delivered_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('dispute_deadline_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pickup_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pickup_code_entered_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('package_photo_url')
                    ->searchable(),
                Tables\Columns\TextColumn::make('buyer_acknowledged_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivery_photo_url')
                    ->searchable(),
                Tables\Columns\IconColumn::make('delivery_confirmed_code')
                    ->boolean(),
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
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
