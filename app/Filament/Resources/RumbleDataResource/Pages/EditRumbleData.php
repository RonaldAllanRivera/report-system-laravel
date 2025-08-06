<?php

namespace App\Filament\Resources\RumbleDataResource\Pages;

use App\Filament\Resources\RumbleDataResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRumbleData extends EditRecord
{
    protected static string $resource = RumbleDataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
