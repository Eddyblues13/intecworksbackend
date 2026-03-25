<?php

namespace App\Filament\Admin\Resources;

use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('service_job_id')->label('Job ID')->disabled(),
            Forms\Components\TextInput::make('payer_id')->label('Payer ID')->disabled(),
            Forms\Components\TextInput::make('amount')->disabled(),
            Forms\Components\TextInput::make('method')->disabled(),
            Forms\Components\Select::make('status')
                ->options([
                    'pending'   => 'Pending',
                    'completed' => 'Completed',
                    'failed'    => 'Failed',
                    'refunded'  => 'Refunded',
                ]),
            Forms\Components\TextInput::make('reference')->disabled(),
            Forms\Components\TextInput::make('purpose')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('payer.full_name')->label('Payer')->searchable(),
                Tables\Columns\TextColumn::make('service_job_id')->label('Job #'),
                Tables\Columns\TextColumn::make('amount')->money('NGN')->sortable(),
                Tables\Columns\TextColumn::make('method'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger'  => 'failed',
                        'info'    => 'refunded',
                    ]),
                Tables\Columns\TextColumn::make('purpose'),
                Tables\Columns\TextColumn::make('reference')->limit(20)->copyable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'   => 'Pending',
                        'completed' => 'Completed',
                        'failed'    => 'Failed',
                        'refunded'  => 'Refunded',
                    ]),
                Tables\Filters\SelectFilter::make('method')
                    ->options([
                        'paystack' => 'Paystack',
                        'korapay'  => 'KoraPay',
                        'card'     => 'Card',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => PaymentResource\Pages\ListPayments::route('/'),
            'edit'  => PaymentResource\Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
