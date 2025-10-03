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
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Google\Client as GoogleClient;
use Google\Service\Gmail as GoogleGmail;
use Google\Service\Gmail\Message as GoogleGmailMessage;
use Google\Service\Gmail\Draft as GoogleGmailDraft;

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
            'payment_link' => $last->payment_link ?? '',
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
        return sprintf('INV-%04d-%03d', $year, $week);
    }

    protected function nextInvoiceNumberForYear(int $year): string
    {
        // Find the highest sequence for the given year (invoice_number formatted as INV-YYYY-NNN)
        $latest = Invoice::where('invoice_number', 'like', sprintf('INV-%04d-%%', $year))
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($latest && preg_match('/^INV-(\d{4})-(\d{3,})$/', $latest->invoice_number, $m)) {
            $seq = (int) $m[2] + 1;
        } else {
            // If none exists yet this year, start at current ISO week
            $seq = Carbon::today()->isoWeek();
        }

        return sprintf('INV-%04d-%03d', $year, $seq);
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
                            ->required(),
                        TextInput::make('invoice_number')->label('Invoice #')->disabled(),
                        Textarea::make('notes')->label('Notes')->rows(3)->columnSpan(3),
                        TextInput::make('payment_link')
                            ->label('Payment Link')
                            ->placeholder('https://wise.com/pay/...')
                            ->url()
                            ->columnSpan(3),
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
                ->action('createAndDownload'),

            Actions\Action::make('connectGoogle')
                ->label('Connect Google')
                ->icon('heroicon-o-link')
                ->color('warning')
                ->url(url('/google/auth'))
                ->openUrlInNewTab()
                ->visible(fn () => $this->needsGoogleAuth()),

            Actions\Action::make('sendEmail')
                ->label('Create Gmail Draft')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn () => !$this->needsGoogleAuth())
                ->form([
                    TextInput::make('to_name')
                        ->label('To Name')
                        ->default('Jesse De Pril')
                        ->required(),
                    TextInput::make('to_email')
                        ->label('To Email')
                        ->email()
                        ->default('finance@logicmedia.be')
                        ->required(),
                    TextInput::make('subject')
                        ->label('Subject')
                        ->default(fn () => $this->defaultEmailSubject())
                        ->required(),
                    Textarea::make('body')
                        ->label('Body')
                        ->rows(12)
                        ->default(fn () => $this->defaultEmailBody())
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->sendEmail($data);
                }),
        ];
    }

    public function createAndDownload()
    {
        $data = $this->form->getState();

        // Use selected invoice date if provided; default to today
        $dateInput = (string) ($data['invoice_date'] ?? '');
        $date = $dateInput ? Carbon::parse($dateInput) : Carbon::today();
        // Keep invoice number sequencing based on current year
        $invoiceNumber = $this->nextInvoiceNumberForYear(Carbon::today()->year);

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
            'payment_link' => (string) ($data['payment_link'] ?? ''),
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

    protected function defaultEmailBody(): string
    {
        $data = $this->form->getState();
        $date = Carbon::today();
        $invoiceNumber = (string) ($data['invoice_number'] ?? '') ?: $this->nextInvoiceNumberForYear($date->year);
        $paymentLink = trim((string) ($data['payment_link'] ?? '')) ?: 'https://wise.com/pay/r/vGcZulPYen3hx54';

        $lines = [
            'Hello Jesse,',
            '',
            "Here's my invoice for this week.",
            'Invoice # ' . $invoiceNumber,
            '',
            "Here's my payment link",
            $paymentLink,
            '',
            'Thanks,',
            'Allan',
        ];

        return implode("\n", $lines);
    }

    protected function defaultEmailSubject(): string
    {
        $data = $this->form->getState();
        $date = Carbon::today();
        $invoiceNumber = (string) ($data['invoice_number'] ?? '') ?: $this->nextInvoiceNumberForYear($date->year);
        return 'Allan Invoice ' . $invoiceNumber;
    }

    public function sendEmail(array $data): void
    {
        $toName = (string) ($data['to_name'] ?? '');
        $toEmail = (string) ($data['to_email'] ?? '');
        $subject = (string) ($data['subject'] ?? '');
        $body = (string) ($data['body'] ?? '');
        // Create Gmail draft (with PDF attachment) using existing Google OAuth token
        try {
            // 1) Create invoice from current form and render PDF into memory
            [$invoice, $pdfFileName, $pdfBytes] = $this->createInvoiceAndPdfFromForm();

            $client = new GoogleClient();
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));
            $client->setRedirectUri(config('services.google.redirect'));
            $client->setAccessType('offline');
            // Ensure Gmail compose scope is included
            $client->addScope(GoogleGmail::GMAIL_COMPOSE);

            $tokenPath = storage_path('app/private/google_oauth_token.json');
            if (!file_exists($tokenPath)) {
                throw new \RuntimeException('Google token not found. Please authorize via /google/auth');
            }
            $accessToken = json_decode((string) file_get_contents($tokenPath), true) ?: [];
            $client->setAccessToken($accessToken);

            if ($client->isAccessTokenExpired()) {
                if ($client->getRefreshToken()) {
                    $refreshed = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                    if (isset($refreshed['error'])) {
                        throw new \RuntimeException('Failed to refresh Google token: ' . $refreshed['error']);
                    }
                    file_put_contents($tokenPath, json_encode($client->getAccessToken()));
                } else {
                    throw new \RuntimeException('Google token expired. Please re-authorize via /google/auth');
                }
            }

            $gmail = new GoogleGmail($client);

            $fromName = config('mail.from.name', 'Allan');
            $fromEmail = config('mail.from.address');
            if (!$fromEmail) {
                $fromEmail = 'no-reply@example.com';
            }

            // Build a multipart/mixed MIME email with PDF attachment
            $boundary = '=_Part_' . bin2hex(random_bytes(12));
            $toHeader = $toName ? ($toName . ' <' . $toEmail . '>') : $toEmail;
            $fromHeader = $fromName ? ($fromName . ' <' . $fromEmail . '>') : $fromEmail;

            $rawMessageString = '';
            $rawMessageString .= 'To: ' . $toHeader . "\r\n";
            $rawMessageString .= 'Subject: ' . $subject . "\r\n";
            $rawMessageString .= 'From: ' . $fromHeader . "\r\n";
            $rawMessageString .= 'MIME-Version: 1.0' . "\r\n";
            $rawMessageString .= 'Content-Type: multipart/mixed; boundary="' . $boundary . '"' . "\r\n\r\n";

            // Text part
            $rawMessageString .= '--' . $boundary . "\r\n";
            $rawMessageString .= 'Content-Type: text/plain; charset="UTF-8"' . "\r\n";
            $rawMessageString .= 'Content-Transfer-Encoding: 7bit' . "\r\n\r\n";
            $rawMessageString .= $body . "\r\n\r\n";

            // Attachment part (PDF)
            $rawMessageString .= '--' . $boundary . "\r\n";
            $rawMessageString .= 'Content-Type: application/pdf; name="' . $pdfFileName . '"' . "\r\n";
            $rawMessageString .= 'Content-Transfer-Encoding: base64' . "\r\n";
            $rawMessageString .= 'Content-Disposition: attachment; filename="' . $pdfFileName . '"' . "\r\n\r\n";
            $rawMessageString .= rtrim(chunk_split(base64_encode($pdfBytes), 76, "\r\n")) . "\r\n\r\n";

            // End boundary
            $rawMessageString .= '--' . $boundary . '--';

            $message = new GoogleGmailMessage();
            $message->setRaw($this->base64UrlEncode($rawMessageString));

            $draft = new GoogleGmailDraft(['message' => $message]);
            $created = $gmail->users_drafts->create('me', $draft);

            $draftId = $created->getId();
            Notification::make()
                ->title('Draft created')
                ->body('Gmail draft created with invoice attached' . ($draftId ? ' (ID: ' . $draftId . ')' : '') . '.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Draft failed')
                ->body('Could not create Gmail draft: ' . $e->getMessage() . '. If needed, re-authorize at /google/auth with gmail.compose scope.')
                ->danger()
                ->send();
        }
    }

    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Create an Invoice based on the current form state and return the model plus PDF bytes and filename.
     * @return array{0: \App\Models\Invoice, 1: string, 2: string}
     */
    protected function createInvoiceAndPdfFromForm(): array
    {
        $data = $this->form->getState();

        // Use selected invoice date if provided; default to today
        $dateInput = (string) ($data['invoice_date'] ?? '');
        $date = $dateInput ? Carbon::parse($dateInput) : Carbon::today();
        // Keep invoice number sequencing based on current year
        $invoiceNumber = $this->nextInvoiceNumberForYear(Carbon::today()->year);

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
            'payment_link' => (string) ($data['payment_link'] ?? ''),
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

        // Render PDF similar to InvoiceController::download
        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice->load('items'),
        ])->setPaper('A4')
          ->setOptions([
              'defaultFont' => 'arialembedded',
              'fontDir' => storage_path('fonts'),
              'fontCache' => storage_path('fonts'),
          ]);

        $fileName = 'Allan - ' . $invoice->invoice_number . '.pdf';
        $pdfBytes = $pdf->output();

        return [$invoice, $fileName, $pdfBytes];
    }
    protected function needsGoogleAuth(): bool
    {
        try {
            $tokenPath = storage_path('app/private/google_oauth_token.json');
            if (!file_exists($tokenPath)) {
                return true;
            }

            $token = json_decode((string) file_get_contents($tokenPath), true) ?: [];
            if (!is_array($token) || (empty($token['access_token']) && empty($token['refresh_token']))) {
                return true;
            }

            // Ensure the saved token includes gmail.compose scope; if not, force re-auth
            $requiredScope = 'https://www.googleapis.com/auth/gmail.compose';
            $tokenScopes = [];
            if (!empty($token['scope'])) {
                // scope can be space-delimited string
                $tokenScopes = is_array($token['scope']) ? $token['scope'] : preg_split('/\s+/', (string) $token['scope']);
            }
            if ($tokenScopes && !in_array($requiredScope, $tokenScopes, true)) {
                return true;
            }

            $client = new GoogleClient();
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));
            $client->setRedirectUri(config('services.google.redirect'));
            $client->setAccessType('offline');
            $client->addScope(GoogleGmail::GMAIL_COMPOSE);
            $client->setAccessToken($token);

            // If expired and no refresh token, needs re-auth. If refresh token exists, we consider it authorized.
            if ($client->isAccessTokenExpired() && !$client->getRefreshToken()) {
                return true;
            }

            return false;
        } catch (\Throwable $e) {
            return true;
        }
    }
}
