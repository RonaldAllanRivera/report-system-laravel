<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RumbleDataResource\Pages;
use App\Filament\Resources\RumbleDataResource\RelationManagers;
use App\Models\RumbleData;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RumbleDataResource extends Resource
{
    protected static ?string $model = RumbleData::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Fieldset::make('Rumble Data')->schema([
    Forms\Components\TextInput::make('campaign')->required(),
    Forms\Components\TextInput::make('spend')->numeric()->required(),
    Forms\Components\TextInput::make('cpm')->numeric()->required(),
    Forms\Components\DatePicker::make('date_from')->required(),
    Forms\Components\DatePicker::make('date_to')->required(),
]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('campaign')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('spend')->sortable(),
                Tables\Columns\TextColumn::make('cpm')->sortable(),
                Tables\Columns\TextColumn::make('date_from')->date()->sortable(),
                Tables\Columns\TextColumn::make('date_to')->date()->sortable(),
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRumbleData::route('/'),
            'create' => Pages\CreateRumbleData::route('/create'),
            'view' => Pages\ViewRumbleData::route('/{record}'),
            'edit' => Pages\EditRumbleData::route('/{record}/edit'),
        ];
    }
}
