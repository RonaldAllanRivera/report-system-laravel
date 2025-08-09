<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GoogleDataResource\Pages;
use App\Models\GoogleData;
use Filament\Resources\Resource;

class GoogleDataResource extends Resource
{
    protected static ?string $model = GoogleData::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Google and Binom Reports Only';
    protected static ?string $navigationLabel = '1. Google Data';
    protected static ?int $navigationSort = 4;

    public static function getPages(): array
    {
        return [
            'index' => Pages\GroupedListGoogleData::route('/'),
        ];
    }
}
