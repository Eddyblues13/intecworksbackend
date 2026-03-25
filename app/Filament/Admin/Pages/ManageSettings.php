<?php

namespace App\Filament\Admin\Pages;

use App\Models\Setting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'API Settings';

    protected static ?string $navigationLabel = 'Payment Gateways';

    protected static ?string $title = 'Payment Gateway API Keys';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.admin.pages.manage-settings';

    // Form state
    public ?array $data = [];

    public function mount(): void
    {
        $this->data = [
            'paystack_public_key' => Setting::get('paystack_public_key', ''),
            'paystack_secret_key' => Setting::get('paystack_secret_key', ''),
            'korapay_public_key' => Setting::get('korapay_public_key', ''),
            'korapay_secret_key' => Setting::get('korapay_secret_key', ''),
            'korapay_encryption_key' => Setting::get('korapay_encryption_key', ''),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Paystack')
                    ->description('Manage your Paystack API credentials.')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        TextInput::make('paystack_public_key')
                            ->label('Public Key')
                            ->placeholder('pk_live_...')
                            ->maxLength(255),
                        TextInput::make('paystack_secret_key')
                            ->label('Secret Key')
                            ->password()
                            ->revealable()
                            ->placeholder('sk_live_...')
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('KoraPay')
                    ->description('Manage your KoraPay API credentials.')
                    ->icon('heroicon-o-building-library')
                    ->schema([
                        TextInput::make('korapay_public_key')
                            ->label('Public Key')
                            ->placeholder('pk_live_...')
                            ->maxLength(255),
                        TextInput::make('korapay_secret_key')
                            ->label('Secret Key')
                            ->password()
                            ->revealable()
                            ->placeholder('sk_live_...')
                            ->maxLength(255),
                        TextInput::make('korapay_encryption_key')
                            ->label('Encryption Key')
                            ->password()
                            ->revealable()
                            ->placeholder('Optional')
                            ->maxLength(255),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($state as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '', 'type' => 'string']
            );
        }

        Notification::make()
            ->title('Settings saved successfully!')
            ->success()
            ->send();
    }
}
