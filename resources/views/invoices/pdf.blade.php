<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 28px; }
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color: #111827; font-size: 12px; }
        .row { display: flex; }
        .between { justify-content: space-between; }
        .right { text-align: right; }
        .muted { color: #6b7280; }
        h1 { font-size: 28px; letter-spacing: 2px; margin: 0; }
        .box { border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; }
        .mt-2 { margin-top: 8px; }
        .mt-4 { margin-top: 16px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-4 { margin-bottom: 16px; }
        .w-50 { width: 48%; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border-bottom: 1px solid #e5e7eb; }
        thead th { background: #0b1b3b; color: #fff; font-weight: 600; text-align: left; }
        tfoot td { font-weight: 600; }
        .money { text-align: right; }
        .label { font-size: 11px; color: #6b7280; margin-bottom: 4px; }
        .small { font-size: 11px; }
    </style>
</head>
<body>
    <div class="row between">
        <div>
            <div class="box" style="min-width: 300px;">
                <div style="font-weight: 600;">{{ $invoice->name }}</div>
                @php($fromLines = array_filter(preg_split('/\r?\n/', trim($invoice->notes ?? ''))))
                @if(false)
                    {{-- Placeholder: if you later want a From address block, store it separately --}}
                @endif
            </div>
        </div>
        <div class="right">
            <h1>INVOICE</h1>
            <div class="row" style="gap: 12px; margin-top: 8px; align-items: center;">
                <div class="label">#</div>
                <div class="box" style="min-width: 110px;">{{ $invoice->invoice_number }}</div>
            </div>
            <div class="row" style="gap: 12px; margin-top: 8px; align-items: center;">
                <div class="label">Date</div>
                <div class="box" style="min-width: 110px;">{{ $invoice->invoice_date->format('d/m/Y') }}</div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <div class="label">Bill To</div>
        <div class="box" style="min-height: 80px; white-space: pre-line;">{{ $invoice->bill_to }}</div>
    </div>

    <div class="mt-4">
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th style="width: 90px;">Quantity</th>
                    <th style="width: 120px;">Rate</th>
                    <th style="width: 140px;" class="right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $it)
                    <tr>
                        <td>{{ $it->item }}</td>
                        <td class="money">{{ number_format($it->quantity) }}</td>
                        <td class="money">$ {{ number_format($it->rate, 2) }}</td>
                        <td class="money">$ {{ number_format($it->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="right">Total</td>
                    <td class="money">$ {{ number_format($invoice->total, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if($invoice->notes)
        <div class="mt-4">
            <div class="label">Notes</div>
            <div class="box small" style="white-space: pre-line;">{{ $invoice->notes }}</div>
        </div>
    @endif
</body>
</html>
