<?php

namespace App\Filament\Resources;

use App\Models\Invoice;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'All Invoices';
    protected static ?string $navigationGroup = 'Tools';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Invoice Details')
                    ->columns(3)
                    ->schema([
                        Textarea::make('name')->label('Name')->rows(6)->columnSpan(1)->required(),
                        Textarea::make('bill_to')->label('Bill To')->rows(6)->columnSpan(1)->required(),
                        DatePicker::make('invoice_date')
                            ->native(false)
                            ->label('Date')
                            ->required()
                            ->default(fn () => today()->toDateString()),
                        TextInput::make('invoice_number')
                            ->label('Invoice #')
                            ->default(fn () => self::nextInvoiceNumberForYear(now()->year))
                            ->unique(ignoreRecord: true)
                            ->required(),
                        Textarea::make('notes')->label('Notes')->rows(3)->columnSpan(3),
                        TextInput::make('payment_link')
                            ->label('Payment Link')
                            ->placeholder('https://...')
                            ->url()
                            ->columnSpan(3),
                        TextInput::make('total')
                            ->label('Total')
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated()
                            ->numeric()
                            ->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->limit(40)
                    ->searchable(),
                TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => '$ ' . number_format((float) $state, 2))
                    ->sortable(),
            ])
            ->filters([
                // Add filters if needed
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Invoice $record) => route('invoices.download', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            InvoiceResource\RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => InvoiceResource\Pages\ListInvoices::route('/'),
            'create' => InvoiceResource\Pages\CreateInvoice::route('/create'),
            'view' => InvoiceResource\Pages\ViewInvoice::route('/{record}'),
            'edit' => InvoiceResource\Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function nextInvoiceNumberForYear(int $year): string
    {
        $latest = Invoice::where('invoice_number', 'like', sprintf('%04d-%%', $year))
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($latest && preg_match('/^(\d{4})-(\d{3,})$/', $latest->invoice_number, $m)) {
            $seq = ((int) $m[2]) + 1;
        } else {
            // Match InvoiceGenerator behavior: start at current ISO week when first of the year
            $seq = now()->isoWeek();
        }

        return sprintf('%04d-%03d', $year, $seq);
    }
}
