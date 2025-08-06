<?php

namespace App\Filament\Resources\RumbleDataResource\Pages;

use App\Filament\Resources\RumbleDataResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRumbleData extends ViewRecord
{
    protected static string $resource = RumbleDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
