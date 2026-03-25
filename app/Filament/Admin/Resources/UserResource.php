<?php

namespace App\Filament\Admin\Resources;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'People';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Personal Info')
                ->schema([
                    Forms\Components\TextInput::make('full_name')->required()->maxLength(255),
                    Forms\Components\TextInput::make('email')->email()->required()->maxLength(255),
                    Forms\Components\TextInput::make('phone')->maxLength(20),
                    Forms\Components\Select::make('role')
                        ->options([
                            'client'   => 'Client',
                            'artisan'  => 'Artisan',
                            'supplier' => 'Supplier',
                            'admin'    => 'Admin',
                        ])->required(),
                    Forms\Components\Select::make('account_status')
                        ->options([
                            'otp_pending'               => 'OTP Pending',
                            'verification_pending'      => 'Verification Pending',
                            'verification_under_review' => 'Under Review',
                            'active'                    => 'Active',
                            'suspended'                 => 'Suspended',
                            'rejected'                  => 'Rejected',
                        ])->required(),
                    Forms\Components\TextInput::make('location')->maxLength(255),
                ])->columns(2),

            Forms\Components\Section::make('Details')
                ->schema([
                    Forms\Components\TextInput::make('trust_score')->numeric()->step(0.1),
                    Forms\Components\TextInput::make('skill_badge')->maxLength(100),
                    Forms\Components\Textarea::make('rejection_reason')->maxLength(500),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('full_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('phone')->searchable(),
                Tables\Columns\BadgeColumn::make('role')
                    ->colors([
                        'primary' => 'client',
                        'success' => 'artisan',
                        'warning' => 'supplier',
                        'danger'  => 'admin',
                    ]),
                Tables\Columns\BadgeColumn::make('account_status')
                    ->colors([
                        'success' => 'active',
                        'warning' => fn ($state) => in_array($state, ['otp_pending', 'verification_pending', 'verification_under_review']),
                        'danger'  => fn ($state) => in_array($state, ['suspended', 'rejected']),
                    ]),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'client'   => 'Client',
                        'artisan'  => 'Artisan',
                        'supplier' => 'Supplier',
                        'admin'    => 'Admin',
                    ]),
                Tables\Filters\SelectFilter::make('account_status')
                    ->options([
                        'active'                    => 'Active',
                        'otp_pending'               => 'OTP Pending',
                        'verification_pending'      => 'Verification Pending',
                        'verification_under_review' => 'Under Review',
                        'suspended'                 => 'Suspended',
                        'rejected'                  => 'Rejected',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => UserResource\Pages\ListUsers::route('/'),
            'create' => UserResource\Pages\CreateUser::route('/create'),
            'edit'   => UserResource\Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
