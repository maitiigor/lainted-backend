<?php

namespace App\Filament\Resources;

use App\Domain\Chat\Models\ContactLeakStrike;
use App\Domain\Listings\Models\Listing;
use App\Domain\Listings\Services\ListingService;
use App\Filament\Resources\ListingResource\Pages;
use App\Support\Enums\BaleType;
use App\Support\Enums\ConditionGrade;
use App\Support\Enums\ItemType;
use App\Support\Enums\ListingSource;
use App\Support\Enums\ListingStatus;
use App\Support\Enums\VerificationStatus;
use App\Support\Enums\WeightTier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ListingResource extends Resource
{
    protected static ?string $model = Listing::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('uuid')
                    ->label('UUID')
                    ->required()
                    ->maxLength(36),
                Forms\Components\Select::make('seller_id')
                    ->relationship('seller', 'name')
                    ->required(),
                Forms\Components\Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required(),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('item_type')
                    ->options(array_column(ItemType::cases(), 'value', 'value'))
                    ->required(),
                Forms\Components\Select::make('condition_grade')
                    ->options(array_column(ConditionGrade::cases(), 'value', 'value'))
                    ->required(),
                Forms\Components\Textarea::make('fault_description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('size')
                    ->maxLength(255),
                Forms\Components\TextInput::make('brand')
                    ->maxLength(255),
                Forms\Components\TextInput::make('model')
                    ->maxLength(255),
                Forms\Components\Textarea::make('serial_number_encrypted')
                    ->columnSpanFull(),
                Forms\Components\Select::make('weight_tier')
                    ->options(array_column(WeightTier::cases(), 'value', 'value'))
                    ->required(),
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('₦'),
                Forms\Components\Toggle::make('is_negotiable')
                    ->required(),
                Forms\Components\TextInput::make('minimum_price')
                    ->numeric(),
                Forms\Components\Toggle::make('is_functional'),
                Forms\Components\Toggle::make('requires_installation')
                    ->required(),
                Forms\Components\Select::make('lga_id')
                    ->relationship('lga', 'name')
                    ->required(),
                Forms\Components\TextInput::make('landmark')
                    ->maxLength(255),
                Forms\Components\Select::make('listing_source')
                    ->options(array_column(ListingSource::cases(), 'value', 'value'))
                    ->required(),
                Forms\Components\Toggle::make('is_bale')
                    ->required(),
                Forms\Components\Select::make('bale_type')
                    ->options(array_column(BaleType::cases(), 'value', 'value')),
                Forms\Components\TextInput::make('bale_item_count')
                    ->numeric(),
                Forms\Components\TextInput::make('bale_origin_country')
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_wardrobe_cleanout')
                    ->required(),
                Forms\Components\Select::make('verification_status')
                    ->options(array_column(VerificationStatus::cases(), 'value', 'value'))
                    ->required()
                    ->default('not_submitted'),
                Forms\Components\TextInput::make('verification_video_path')
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('verified_at'),
                Forms\Components\TextInput::make('verified_by')
                    ->numeric(),
                Forms\Components\Toggle::make('is_platform_verified')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options(array_column(ListingStatus::cases(), 'value', 'value'))
                    ->required()
                    ->default('draft'),
                Forms\Components\TextInput::make('views_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\DateTimePicker::make('published_at'),
                Forms\Components\DateTimePicker::make('expires_at'),
                Forms\Components\DateTimePicker::make('sold_at'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Photos')
                    ->schema([
                        RepeatableEntry::make('photos')
                            ->label('')
                            ->schema([
                                ImageEntry::make('url')->label(''),
                            ])
                            ->columns(6),
                    ]),
                InfolistSection::make('Contact-leak strikes')
                    ->description('Automatically detected attempts to share contact info in this listing\'s text.')
                    ->schema([
                        RepeatableEntry::make('contactLeakStrikes')
                            ->label('')
                            ->state(
                                fn (Listing $record) => ContactLeakStrike::where('source_type', 'listing')
                                    ->where('source_id', $record->id)
                                    ->latest()
                                    ->get()
                            )
                            ->schema([
                                // Every entry below uses an explicit `state()`
                                // closure rather than bare `make('column')`.
                                // `ComponentContainer::getState()` falls back
                                // to the *parent* container's state (the
                                // outer Listing record, since this whole
                                // repeatable's rows come from a custom
                                // `state()` closure rather than a real
                                // Eloquent relationship Filament can trace)
                                // when no explicit array/record is resolved
                                // for the row itself - so bare attribute-name
                                // resolution silently reads off the wrong
                                // model. An explicit closure receives the
                                // correct per-row $record directly and
                                // sidesteps that entirely - confirmed by
                                // dumping the rendered HTML during
                                // development, not assumed.
                                TextEntry::make('detected_pattern')
                                    ->badge()
                                    ->state(fn (ContactLeakStrike $record) => $record->detected_pattern->value),
                                TextEntry::make('matched_text')
                                    ->state(fn (ContactLeakStrike $record) => $record->matched_text),
                                TextEntry::make('created_at')
                                    ->dateTime()
                                    ->state(fn (ContactLeakStrike $record) => $record->created_at),
                            ])
                            ->columns(3),
                    ])
                    ->collapsed(fn (Listing $record) => ContactLeakStrike::where('source_type', 'listing')
                        ->where('source_id', $record->id)
                        ->doesntExist()
                    ),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('seller.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('item_type')
                    ->formatStateUsing(fn ($state) => $state?->value)
                    ->searchable(),
                Tables\Columns\TextColumn::make('condition_grade')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->value)
                    ->searchable(),
                Tables\Columns\TextColumn::make('size')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('brand')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('model')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('weight_tier')
                    ->formatStateUsing(fn ($state) => $state?->value)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('price')
                    ->money('NGN')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_negotiable')
                    ->boolean(),
                Tables\Columns\TextColumn::make('minimum_price')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_functional')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('requires_installation')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('lga.name')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('landmark')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('listing_source')
                    ->formatStateUsing(fn ($state) => $state?->value)
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_bale')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('bale_type')
                    ->formatStateUsing(fn ($state) => $state?->value)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('bale_item_count')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('bale_origin_country')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_wardrobe_cleanout')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('verification_status')
                    ->formatStateUsing(fn ($state) => $state?->value)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_platform_verified')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->value)
                    ->searchable(),
                Tables\Columns\TextColumn::make('views_count')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('sold_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Filters\SelectFilter::make('status')
                    ->options(array_column(ListingStatus::cases(), 'value', 'value')),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('remove')
                    ->label('Remove')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Listing $record) => $record->status !== ListingStatus::Removed)
                    ->action(function (Listing $record) {
                        app(ListingService::class)->remove($record);

                        Notification::make()
                            ->title('Listing removed')
                            ->success()
                            ->send();
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
            'index' => Pages\ListListings::route('/'),
            'create' => Pages\CreateListing::route('/create'),
            'view' => Pages\ViewListing::route('/{record}'),
            'edit' => Pages\EditListing::route('/{record}/edit'),
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
