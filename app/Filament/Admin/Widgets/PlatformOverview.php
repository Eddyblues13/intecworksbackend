<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Escrow;
use App\Models\Payment;
use App\Models\ServiceJob;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->description('All registered users')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Artisans', User::where('role', 'artisan')->count())
                ->description('Active artisans')
                ->descriptionIcon('heroicon-o-wrench')
                ->color('success'),

            Stat::make('Clients', User::where('role', 'client')->count())
                ->description('Active clients')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('info'),

            Stat::make('Total Jobs', ServiceJob::count())
                ->description('All service jobs')
                ->descriptionIcon('heroicon-o-wrench-screwdriver')
                ->color('warning'),

            Stat::make('Active Jobs', ServiceJob::whereIn('status', ['accepted', 'inspection', 'work_in_progress', 'escrow_funded'])->count())
                ->description('Jobs currently in progress')
                ->descriptionIcon('heroicon-o-clock')
                ->color('success'),

            Stat::make('Payments', '₦' . number_format(Payment::where('status', 'completed')->sum('amount'), 2))
                ->description('Total completed payments')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success'),
        ];
    }
}
