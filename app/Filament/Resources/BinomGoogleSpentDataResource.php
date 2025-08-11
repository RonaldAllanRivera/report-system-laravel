<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BinomGoogleSpentDataResource\Pages;
use App\Models\BinomGoogleSpentData;
use Filament\Resources\Resource;

class BinomGoogleSpentDataResource extends Resource
{
    protected static ?string $model = BinomGoogleSpentData::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';
    protected static ?string $navigationGroup = 'Google and Binom Reports Only';
    protected static ?string $navigationLabel = '2. Binom Google Spent Data';
    protected static ?int $navigationSort = 2;

    public static function getPages(): array
    {
        return [
            'index' => Pages\GroupedListBinomGoogleSpentData::route('/'),
        ];
    }
}
