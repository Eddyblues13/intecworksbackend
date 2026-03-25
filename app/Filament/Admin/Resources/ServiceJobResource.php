<?php

namespace App\Filament\Admin\Resources;

use App\Models\ServiceJob;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceJobResource extends Resource
{
    protected static ?string $model = ServiceJob::class;
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Jobs';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Job Details')
                ->schema([
                    Forms\Components\Select::make('client_id')
                        ->relationship('client', 'full_name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('artisan_id')
                        ->relationship('artisan', 'full_name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('category_id')
                        ->relationship('category', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('status')
                        ->options([
                            'open'             => 'Open',
                            'accepted'         => 'Accepted',
                            'inspection'       => 'Inspection',
                            'scope_classified' => 'Scope Classified',
                            'quote_ready'      => 'Quote Ready',
                            'quote_approved'   => 'Quote Approved',
                            'escrow_funded'    => 'Escrow Funded',
                            'work_in_progress' => 'Work In Progress',
                            'completed'        => 'Completed',
                            'closed'           => 'Closed',
                            'cancelled'        => 'Cancelled',
                            'disputed'         => 'Disputed',
                        ])->required(),
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                    Forms\Components\TextInput::make('location'),
                    Forms\Components\TextInput::make('progress_percent')->numeric()->suffix('%'),
                    Forms\Components\TextInput::make('scope_classification')->label('Scope'),
                    Forms\Components\Textarea::make('progress_notes')->columnSpanFull(),
                    Forms\Components\Textarea::make('completion_notes')->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Job Overview')
                ->icon('heroicon-o-wrench-screwdriver')
                ->schema([
                    Infolists\Components\TextEntry::make('id')->label('Job #'),
                    Infolists\Components\TextEntry::make('client.full_name')->label('Client'),
                    Infolists\Components\TextEntry::make('artisan.full_name')->label('Artisan')->default('Not assigned'),
                    Infolists\Components\TextEntry::make('category.name')->label('Category'),
                    Infolists\Components\TextEntry::make('status')
                        ->badge()
                        ->color(fn (string $state): string => match (true) {
                            $state === 'open' => 'primary',
                            in_array($state, ['accepted', 'inspection', 'scope_classified', 'quote_ready']) => 'warning',
                            in_array($state, ['quote_approved', 'escrow_funded']) => 'info',
                            in_array($state, ['work_in_progress', 'completed', 'closed']) => 'success',
                            default => 'danger',
                        }),
                    Infolists\Components\TextEntry::make('scope_classification')->label('Scope')->default('N/A'),
                ])->columns(3),

            Infolists\Components\Section::make('Description & Location')
                ->schema([
                    Infolists\Components\TextEntry::make('description')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('location'),
                    Infolists\Components\TextEntry::make('job_type')->label('Job Type'),
                ])->columns(2),

            Infolists\Components\Section::make('Progress')
                ->icon('heroicon-o-chart-bar')
                ->schema([
                    Infolists\Components\TextEntry::make('progress_percent')
                        ->label('Progress')
                        ->suffix('%')
                        ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                    Infolists\Components\TextEntry::make('progress_notes')->label('Progress Notes')->default('No notes yet'),
                    Infolists\Components\TextEntry::make('completion_notes')->label('Completion Notes')->default('N/A'),
                ])->columns(2),

            Infolists\Components\Section::make('Timeline')
                ->icon('heroicon-o-clock')
                ->schema([
                    Infolists\Components\TextEntry::make('created_at')->dateTime()->label('Created'),
                    Infolists\Components\TextEntry::make('accepted_at')->dateTime()->label('Accepted')->default('—'),
                    Infolists\Components\TextEntry::make('started_at')->dateTime()->label('Started')->default('—'),
                    Infolists\Components\TextEntry::make('inspection_submitted_at')->dateTime()->label('Inspection Submitted')->default('—'),
                    Infolists\Components\TextEntry::make('scope_classified_at')->dateTime()->label('Scope Classified')->default('—'),
                    Infolists\Components\TextEntry::make('quote_submitted_at')->dateTime()->label('Quote Submitted')->default('—'),
                    Infolists\Components\TextEntry::make('completed_at')->dateTime()->label('Completed')->default('—'),
                    Infolists\Components\TextEntry::make('closed_at')->dateTime()->label('Closed')->default('—'),
                ])->columns(4),

            Infolists\Components\Section::make('Escrow')
                ->icon('heroicon-o-shield-check')
                ->schema([
                    Infolists\Components\TextEntry::make('escrow.status')->label('Escrow Status')->badge()->default('N/A'),
                    Infolists\Components\TextEntry::make('escrow.deposit_amount')->label('Deposit')->money('NGN')->default('—'),
                    Infolists\Components\TextEntry::make('escrow.remaining_amount')->label('Remaining')->money('NGN')->default('—'),
                    Infolists\Components\TextEntry::make('escrow.total_funded')->label('Total Funded')->money('NGN')->default('—'),
                    Infolists\Components\TextEntry::make('escrow.total_released')->label('Released')->money('NGN')->default('—'),
                ])->columns(5),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('client.full_name')->label('Client')->searchable(),
                Tables\Columns\TextColumn::make('artisan.full_name')->label('Artisan')->searchable(),
                Tables\Columns\TextColumn::make('category.name')->label('Category'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'primary'   => 'open',
                        'warning'   => fn ($state) => in_array($state, ['accepted', 'inspection', 'scope_classified', 'quote_ready']),
                        'info'      => fn ($state) => in_array($state, ['quote_approved', 'escrow_funded']),
                        'success'   => fn ($state) => in_array($state, ['work_in_progress', 'completed', 'closed']),
                        'danger'    => fn ($state) => in_array($state, ['cancelled', 'disputed']),
                    ]),
                Tables\Columns\TextColumn::make('progress_percent')->suffix('%')->label('Progress'),
                Tables\Columns\TextColumn::make('location')->limit(30),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open'             => 'Open',
                        'accepted'         => 'Accepted',
                        'inspection'       => 'Inspection',
                        'scope_classified' => 'Scope Classified',
                        'quote_ready'      => 'Quote Ready',
                        'quote_approved'   => 'Quote Approved',
                        'escrow_funded'    => 'Escrow Funded',
                        'work_in_progress' => 'Work In Progress',
                        'completed'        => 'Completed',
                        'closed'           => 'Closed',
                        'cancelled'        => 'Cancelled',
                        'disputed'         => 'Disputed',
                    ]),
                Tables\Filters\Filter::make('in_progress')
                    ->label('In Progress Only')
                    ->query(fn ($query) => $query->where('status', 'work_in_progress'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel this job?')
                    ->modalDescription('This will mark the job as cancelled. This action cannot be undone.')
                    ->visible(fn ($record) => !in_array($record->status, ['completed', 'closed', 'cancelled']))
                    ->action(fn ($record) => $record->update(['status' => 'cancelled'])),
                Tables\Actions\Action::make('markCompleted')
                    ->label('Mark Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'work_in_progress')
                    ->action(fn ($record) => $record->update([
                        'status'       => 'completed',
                        'completed_at' => now(),
                        'progress_percent' => 100,
                    ])),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            ServiceJobResource\RelationManagers\QuotesRelationManager::class,
            ServiceJobResource\RelationManagers\PaymentsRelationManager::class,
            ServiceJobResource\RelationManagers\MaterialRequestsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ServiceJobResource\Pages\ListServiceJobs::route('/'),
            'view'  => ServiceJobResource\Pages\ViewServiceJob::route('/{record}'),
            'edit'  => ServiceJobResource\Pages\EditServiceJob::route('/{record}/edit'),
        ];
    }
}
