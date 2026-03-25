<?php

namespace App\Filament\Admin\Resources;

use App\Models\Escrow;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EscrowResource extends Resource
{
    protected static ?string $model = Escrow::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false; // escrows are system-created
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('job_id')->label('Job ID')->disabled(),
            Forms\Components\TextInput::make('client_id')->label('Client ID')->disabled(),
            Forms\Components\Select::make('status')
                ->options([
                    'awaiting_deposit'           => 'Awaiting Deposit',
                    'deposit_paid'               => 'Deposit Paid',
                    'awaiting_remaining_balance'  => 'Awaiting Remaining',
                    'fully_funded'               => 'Fully Funded',
                    'released'                   => 'Released',
                    'refunded'                   => 'Refunded',
                    'disputed'                   => 'Disputed',
                ]),
            Forms\Components\TextInput::make('deposit_amount')->numeric()->prefix('₦')->disabled(),
            Forms\Components\TextInput::make('remaining_amount')->numeric()->prefix('₦')->disabled(),
            Forms\Components\TextInput::make('total_funded')->numeric()->prefix('₦')->disabled(),
            Forms\Components\TextInput::make('total_released')->numeric()->prefix('₦')->disabled(),
            Forms\Components\TextInput::make('total_refunded')->numeric()->prefix('₦')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('job.id')->label('Job #'),
                Tables\Columns\TextColumn::make('client.full_name')->label('Client')->searchable(),
                Tables\Columns\TextColumn::make('deposit_amount')->money('NGN')->label('Deposit'),
                Tables\Columns\TextColumn::make('remaining_amount')->money('NGN')->label('Remaining'),
                Tables\Columns\TextColumn::make('total_funded')->money('NGN')->label('Funded'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => fn ($state) => in_array($state, ['awaiting_deposit', 'awaiting_remaining_balance']),
                        'info'    => 'deposit_paid',
                        'success' => fn ($state) => in_array($state, ['fully_funded', 'released']),
                        'danger'  => fn ($state) => in_array($state, ['refunded', 'disputed']),
                    ]),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'awaiting_deposit'           => 'Awaiting Deposit',
                        'fully_funded'               => 'Fully Funded',
                        'released'                   => 'Released',
                        'disputed'                   => 'Disputed',
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
            'index' => EscrowResource\Pages\ListEscrows::route('/'),
            'edit'  => EscrowResource\Pages\EditEscrow::route('/{record}/edit'),
        ];
    }
}
