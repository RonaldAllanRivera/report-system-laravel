<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RumbleCampaignDataResource\Pages;
use App\Models\RumbleCampaignData;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RumbleCampaignDataResource extends Resource
{
    protected static ?string $model = RumbleCampaignData::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Rumble Data';
    protected static ?string $navigationLabel = 'Campaign Data';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('cpm')
                    ->numeric()
                    ->required()
                    ->prefix('$')
                    ->label('CPM'),
                Forms\Components\TextInput::make('daily_limit')
                    ->numeric()
                    ->label('Daily Limit'),
                Forms\Components\DatePicker::make('date_from')->required(),
                Forms\Components\DatePicker::make('date_to')->required(),
                Forms\Components\Select::make('report_type')
                    ->options(RumbleCampaignData::getReportTypeOptions())
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('cpm')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('daily_limit')->label('Daily Limit')->sortable(),
                Tables\Columns\TextColumn::make('report_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'daily' => 'success',
                        'weekly' => 'primary',
                        'monthly' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_from')->date()->sortable(),
                Tables\Columns\TextColumn::make('date_to')->date()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // add filters later as needed
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\GroupedListRumbleCampaignData::route('/'),
            'list' => Pages\ListRumbleCampaignData::route('/list'),
            'create' => Pages\CreateRumbleCampaignData::route('/create'),
            'view' => Pages\ViewRumbleCampaignData::route('/{record}'),
            'edit' => Pages\EditRumbleCampaignData::route('/{record}/edit'),
        ];
    }
}
