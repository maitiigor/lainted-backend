<?php

namespace App\Filament\Pages;

use App\Domain\Payments\Models\PlatformSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Hand-rolled singleton settings page - no settings package, matching
 * this project's preference for avoiding dependencies for simple things
 * (see the "no laravel-paystack package" precedent). Backs the one
 * admin-configurable knob this app has: the personal-seller commission
 * rate applied at escrow release (see EscrowService).
 */
class PlatformSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.platform-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(PlatformSetting::current()->only(['personal_commission_rate']));
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('personal_commission_rate')
                    ->label('Personal seller commission (%)')
                    ->helperText('Applied to the item price at escrow release. Business sellers always pay 0% - subscription-funded per the product plan.')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->maxValue(100),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        PlatformSetting::current()->update($this->form->getState());

        Notification::make()->title('Settings saved.')->success()->send();
    }
}
