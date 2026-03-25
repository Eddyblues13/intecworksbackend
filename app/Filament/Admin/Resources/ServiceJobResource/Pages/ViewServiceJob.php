<?php

namespace App\Filament\Admin\Resources\ServiceJobResource\Pages;

use App\Filament\Admin\Resources\ServiceJobResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewServiceJob extends ViewRecord
{
    protected static string $resource = ServiceJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
