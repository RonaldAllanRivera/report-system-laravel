<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(4)
                    ->schema([
                        TextInput::make('item')
                            ->label('Item')
                            ->required()
                            ->columnSpan(2),
                        TextInput::make('quantity')
                            ->label('Qty')
                            ->numeric()
                            ->default(1)
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                $rate = (float) $get('rate');
                                $set('amount', round(((float) $state) * $rate, 2));
                            }),
                        TextInput::make('rate')
                            ->label('Rate')
                            ->numeric()
                            ->default(0)
                            ->prefix('$')
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                $qty = (float) $get('quantity');
                                $set('amount', round($qty * ((float) $state), 2));
                            }),
                        TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('item')->label('Item')->wrap()->searchable(),
                TextColumn::make('quantity')->label('Qty')->sortable(),
                TextColumn::make('rate')->label('Rate')->formatStateUsing(fn ($s) => '$ ' . number_format((float) $s, 2))->sortable(),
                TextColumn::make('amount')->label('Amount')->formatStateUsing(fn ($s) => '$ ' . number_format((float) $s, 2))->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['amount'] = round(((float) ($data['quantity'] ?? 0)) * ((float) ($data['rate'] ?? 0)), 2);
                        return $data;
                    })
                    ->after(function (RelationManager $livewire) {
                        $invoice = $livewire->getOwnerRecord();
                        $invoice->update(['total' => $invoice->computeTotal()]);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['amount'] = round(((float) ($data['quantity'] ?? 0)) * ((float) ($data['rate'] ?? 0)), 2);
                        return $data;
                    })
                    ->after(function (RelationManager $livewire) {
                        $invoice = $livewire->getOwnerRecord();
                        $invoice->update(['total' => $invoice->computeTotal()]);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function (RelationManager $livewire) {
                        $invoice = $livewire->getOwnerRecord();
                        $invoice->update(['total' => $invoice->computeTotal()]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function (RelationManager $livewire) {
                            $invoice = $livewire->getOwnerRecord();
                            $invoice->update(['total' => $invoice->computeTotal()]);
                        }),
                ]),
            ]);
    }
}
