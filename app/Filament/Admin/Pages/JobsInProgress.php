<?php

namespace App\Filament\Admin\Pages;

use App\Models\ServiceJob;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class JobsInProgress extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Jobs In Progress';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?int $navigationSort = 2;
    protected static ?string $title = 'Jobs In Progress';

    protected static string $view = 'filament.admin.pages.jobs-in-progress';

    public function table(Table $table): Table
    {
        return $table
            ->query(ServiceJob::query()->where('status', 'work_in_progress'))
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('client.full_name')->label('Client')->searchable(),
                Tables\Columns\TextColumn::make('artisan.full_name')->label('Artisan')->searchable(),
                Tables\Columns\TextColumn::make('category.name')->label('Category'),
                Tables\Columns\TextColumn::make('progress_percent')->suffix('%')->label('Progress')->sortable(),
                Tables\Columns\TextColumn::make('location')->limit(25),
                Tables\Columns\TextColumn::make('started_at')->dateTime()->label('Started')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->label('Created')->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.admin.resources.service-jobs.view', $record)),
                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->url(fn ($record) => route('filament.admin.resources.service-jobs.edit', $record)),
                Tables\Actions\Action::make('markCompleted')
                    ->label('Mark Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update([
                        'status'           => 'completed',
                        'completed_at'     => now(),
                        'progress_percent' => 100,
                    ])),
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update(['status' => 'cancelled'])),
            ])
            ->defaultSort('started_at', 'desc')
            ->emptyStateHeading('No jobs in progress')
            ->emptyStateDescription('All jobs are either completed or not yet started.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ServiceJob::where('status', 'work_in_progress')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
