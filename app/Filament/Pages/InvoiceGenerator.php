<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class InvoiceGenerator extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Invoice';
    protected static ?string $navigationGroup = 'Tools';
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.invoice-generator';

    public ?array $data = [];

    public function mount(): void
    {
        $date = today();

        // Prefill from the most recent invoice
        $last = Invoice::with('items')->latest('id')->first();

        $items = [];
        if ($last && $last->items->count()) {
            foreach ($last->items as $it) {
                $items[] = [
                    'item' => (string) $it->item,
                    'quantity' => (int) $it->quantity,
                    'rate' => (float) $it->rate,
                    'amount' => (float) $it->amount,
                ];
            }
        } else {
            $items[] = ['item' => '', 'quantity' => 1, 'rate' => 0, 'amount' => 0];
        }

        $total = 0;
        foreach ($items as $row) {
            $total += (float) ($row['amount'] ?? 0);
        }

        $this->form->fill([
            'name' => $last->name ?? (auth()->user()->name ?? 'Allan'),
            'bill_to' => $last->bill_to ?? '',
            'invoice_date' => $date->toDateString(),
            'invoice_number' => $this->nextInvoiceNumberForYear($date->year),
            'notes' => $last->notes ?? '',
            'items' => $items,
            'total' => round($total, 2),
        ]);
    }

    protected function defaultInvoiceDate(): Carbon
    {
        // Date should always be today
        return today();
    }

    protected function generateInvoiceNumber(Carbon $date): string
    {
        $year = $date->year;
        $week = $date->isoWeek();
        return sprintf('%04d-%03d', $year, $week);
    }

    protected function nextInvoiceNumberForYear(int $year): string
    {
        // Find the highest sequence for the given year (invoice_number formatted as YYYY-NNN)
        $latest = Invoice::where('invoice_number', 'like', sprintf('%04d-%%', $year))
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($latest && preg_match('/^(\d{4})-(\d{3,})$/', $latest->invoice_number, $m)) {
            $seq = (int) $m[2] + 1;
        } else {
            // If none exists yet this year, start at current ISO week
            $seq = Carbon::today()->isoWeek();
        }

        return sprintf('%04d-%03d', $year, $seq);
    }

    public function form(Form $form): Form
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
                            ->disabled(),
                        TextInput::make('invoice_number')->label('Invoice #')->disabled(),
                        Textarea::make('notes')->label('Notes')->rows(3)->columnSpan(3),
                    ]),

                Section::make('Items')
                    ->schema([
                        Repeater::make('items')
                            ->label('Line Items')
                            ->addActionLabel('Line item')
                            ->columns(12)
                            ->reactive()
                            ->afterStateUpdated(function (Set $set, $state) {
                                $total = 0;
                                foreach ((array) $state as $row) {
                                    $qty = (float) ($row['quantity'] ?? 0);
                                    $rate = (float) ($row['rate'] ?? 0);
                                    $total += $qty * $rate;
                                }
                                $set('total', round($total, 2));
                            })
                            ->defaultItems(1)
                            ->schema([
                                TextInput::make('item')->label('Item')->required()->columnSpan(6),
                                TextInput::make('quantity')
                                    ->label('Qty')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $qty = (float) $get('quantity');
                                        $rate = (float) $get('rate');
                                        $set('amount', round($qty * $rate, 2));
                                    })
                                    ->columnSpan(2),
                                TextInput::make('rate')
                                    ->label('Rate')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step('0.01')
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $qty = (float) $get('quantity');
                                        $rate = (float) $get('rate');
                                        $set('amount', round($qty * $rate, 2));
                                    })
                                    ->columnSpan(2),
                                TextInput::make('amount')
                                    ->label('Amount')
                                    ->numeric()
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(2),
                            ]),
                        Grid::make()
                            ->columns(12)
                            ->schema([
                                TextInput::make('total')
                                    ->label('Total')
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(3),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action('createAndDownload')
        ];
    }

    public function createAndDownload()
    {
        $data = $this->form->getState();

        // Always use today's date and compute the next unique invoice number for the year
        $date = Carbon::today();
        $invoiceNumber = $this->nextInvoiceNumberForYear($date->year);

        $items = (array) ($data['items'] ?? []);
        $total = 0;
        foreach ($items as &$r) {
            $qty = (float) ($r['quantity'] ?? 0);
            $rate = (float) ($r['rate'] ?? 0);
            $r['amount'] = round($qty * $rate, 2);
            $total += $r['amount'];
        }
        unset($r);

        $invoice = Invoice::create([
            'name' => (string) ($data['name'] ?? ''),
            'bill_to' => (string) ($data['bill_to'] ?? ''),
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $date->toDateString(),
            'notes' => (string) ($data['notes'] ?? ''),
            'total' => round($total, 2),
        ]);

        foreach ($items as $row) {
            if (!trim((string) ($row['item'] ?? ''))) {
                continue;
            }
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'item' => (string) $row['item'],
                'quantity' => (int) ($row['quantity'] ?? 0),
                'rate' => (float) ($row['rate'] ?? 0),
                'amount' => (float) ($row['amount'] ?? 0),
            ]);
        }

        return redirect()->route('invoices.download', $invoice);
    }
}
