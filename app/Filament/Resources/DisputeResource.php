<?php

namespace App\Filament\Resources;

use App\Domain\Trust\Models\Dispute;
use App\Domain\Trust\Services\DisputeService;
use App\Filament\Resources\DisputeResource\Pages;
use App\Support\Enums\DisputeStatus;
use App\Support\Enums\DisputeType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Throwable;

/**
 * Disputes are never admin-created or hand-edited (no create/edit pages,
 * matching EscrowResource/TransactionResource's system-managed
 * philosophy) - this exists purely to review open disputes and resolve
 * them via the three actions below, each backed by DisputeService.
 */
class DisputeResource extends Resource
{
    protected static ?string $model = Dispute::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationGroup = 'Trust & Safety';

    private const OPEN_STATUSES = [
        DisputeStatus::Open,
        DisputeStatus::SelfResolving,
        DisputeStatus::AdminReview,
    ];

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('order.uuid')->label('Order')->disabled(),
                Forms\Components\Select::make('dispute_type')
                    ->options(collect(DisputeType::cases())->mapWithKeys(fn ($c) => [$c->value => $c->name])->all())
                    ->disabled(),
                Forms\Components\Textarea::make('buyer_statement')->disabled()->columnSpanFull(),
                Forms\Components\Textarea::make('seller_statement')->disabled()->columnSpanFull(),
                Forms\Components\TextInput::make('self_resolution_offered')->numeric()->disabled(),
                Forms\Components\Toggle::make('self_resolution_accepted')->disabled(),
                Forms\Components\Select::make('status')
                    ->options(collect(DisputeStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->name])->all())
                    ->disabled(),
                Forms\Components\Textarea::make('admin_notes')->disabled()->columnSpanFull(),
                Forms\Components\DateTimePicker::make('resolved_at')->disabled(),
                Forms\Components\DateTimePicker::make('self_resolve_expires_at')->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.uuid')
                    ->label('Order')
                    ->searchable(),
                Tables\Columns\TextColumn::make('dispute_type')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('self_resolution_offered')
                    ->money('NGN')
                    ->sortable(),
                Tables\Columns\TextColumn::make('admin.name')
                    ->label('Resolved by'),
                Tables\Columns\TextColumn::make('resolved_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('self_resolve_expires_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('release')
                    ->label('Release to seller')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Dispute $record) => in_array($record->status, self::OPEN_STATUSES, true))
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')->label('Notes'),
                    ])
                    ->action(fn (Dispute $record, array $data) => self::resolve(
                        $record,
                        fn (DisputeService $service, $admin) => $service->adminResolveRelease($record, $admin, $data['admin_notes'] ?? null),
                    )),
                Tables\Actions\Action::make('refund')
                    ->label('Full refund')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Dispute $record) => in_array($record->status, self::OPEN_STATUSES, true))
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')->label('Notes'),
                    ])
                    ->action(fn (Dispute $record, array $data) => self::resolve(
                        $record,
                        fn (DisputeService $service, $admin) => $service->adminResolveRefund($record, $admin, $data['admin_notes'] ?? null),
                    )),
                Tables\Actions\Action::make('partial')
                    ->label('Partial split')
                    ->icon('heroicon-o-scale')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Dispute $record) => in_array($record->status, self::OPEN_STATUSES, true))
                    ->form([
                        Forms\Components\TextInput::make('refund_amount')
                            ->label('Refund to buyer (₦)')
                            ->numeric()
                            ->required(),
                        Forms\Components\Textarea::make('admin_notes')->label('Notes'),
                    ])
                    ->action(fn (Dispute $record, array $data) => self::resolve(
                        $record,
                        fn (DisputeService $service, $admin) => $service->adminResolvePartial($record, $admin, (float) $data['refund_amount'], $data['admin_notes'] ?? null),
                    )),
            ])
            ->bulkActions([]);
    }

    private static function resolve(Dispute $record, \Closure $call): void
    {
        try {
            $call(app(DisputeService::class), auth()->user());

            Notification::make()->title('Dispute resolved.')->success()->send();
        } catch (Throwable $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDisputes::route('/'),
            'view' => Pages\ViewDispute::route('/{record}'),
        ];
    }
}
