<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BinomRumbleSpentDataResource\Pages;
use App\Models\BinomRumbleSpentData;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BinomRumbleSpentDataResource extends Resource
{
    protected static ?string $model = BinomRumbleSpentData::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Fieldset::make('Binom Rumble Spent Data')->schema([
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\TextInput::make('leads')->numeric()->required(),
                    Forms\Components\TextInput::make('revenue')->numeric()->required(),
                    Forms\Components\DatePicker::make('date_from')->required(),
                    Forms\Components\DatePicker::make('date_to')->required(),
                    Forms\Components\Select::make('report_type')
                        ->options([
                            'daily' => 'Daily',
                            'weekly' => 'Weekly',
                            'monthly' => 'Monthly',
                        ])->required(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('leads')
                    ->sortable(),
                Tables\Columns\TextColumn::make('revenue')
                    ->sortable()
                    ->money('USD'),
                Tables\Columns\TextColumn::make('date_from')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_to')
                    ->date()
                    ->sortable(),
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
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('report_type')
                    ->options([
                        'daily' => 'Daily',
                        'weekly' => 'Weekly',
                        'monthly' => 'Monthly',
                    ]),
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
            'index' => Pages\GroupedListBinomRumbleSpentData::route('/'),
        ];
    }
}
