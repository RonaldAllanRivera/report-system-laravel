<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 28px; }
        /* Embed Arial so DomPDF uses the exact font */
        @font-face {
            font-family: 'ArialEmbedded';
            font-style: normal;
            font-weight: 400;
            src: url('file:///{{ str_replace('\\', '/', public_path('fonts/arial.ttf')) }}') format('truetype');
        }
        @font-face {
            font-family: 'ArialEmbedded';
            font-style: normal;
            font-weight: 700;
            src: url('file:///{{ str_replace('\\', '/', public_path('fonts/arialbd.ttf')) }}') format('truetype');
        }
        body { font-family: arialembedded, sans-serif; color: #111827; font-size: 12px; }
        *, table, th, td, h1, h2, h3, h4, h5, h6 { font-family: arialembedded, sans-serif !important; }
        .row { display: flex; }
        .between { justify-content: space-between; }
        .right { text-align: right; }
        .muted { color: #6b7280; }
        h1 { font-size: 46px; letter-spacing: 3px; margin: 0; font-weight: 700; color: #3f3f3f; font-family: arialembedded, Arial, Helvetica, sans-serif !important; }
        .box { border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; }
        .mt-2 { margin-top: 8px; }
        .mt-4 { margin-top: 16px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-4 { margin-bottom: 16px; }
        .w-50 { width: 48%; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border-bottom: 1px solid #e5e7eb; }
        thead th { background: #3f3f3f; color: #fff; font-weight: 700; text-align: left; border-bottom: 0; }
        thead th:first-child { border-top-left-radius: 8px; }
        thead th:last-child { border-top-right-radius: 8px; }
        tfoot td { font-weight: 600; }
        .money { text-align: right; }
        .center { text-align: center; }
        .label { font-size: 11px; color: #6b7280; margin-bottom: 4px; }
        .small { font-size: 11px; }
        .totals td { padding: 6px 10px; border-bottom: 0; }
        .header { display: table; width: 100%; }
        .header .left, .header .right { display: table-cell; vertical-align: top; }
        .header .right { text-align: right; }
        .invoice-no { color: #9ca3af; margin-top: 6px; font-size: 16px; }
        .meta { margin-top: 8px; width: 330px; margin-left: auto; }
        .meta td { padding: 4px 0; border: 0; }
        .balance { background: #f3f4f6; border-radius: 8px; padding: 10px 14px; display: inline-block; width: 330px; margin-top: 10px; }
        .balance .label { font-weight: 700; color: #111827; }
        .balance .amount { float: right; font-weight: 700; }
    </style>
</head>
<body>
    <div class="header">
        <div class="left">
            @php($fromLines = array_values(array_filter(preg_split('/\r?\n/', (string) $invoice->name))))
            @if(count($fromLines))
                <div style="font-weight: 700;">{{ $fromLines[0] }}</div>
                @if(count($fromLines) > 1)
                    <div style="white-space: pre-line;">{{ implode("\n", array_slice($fromLines, 1)) }}</div>
                @endif
            @else
                <div style="font-weight: 700;">{{ $invoice->name }}</div>
            @endif
        </div>
        <div class="right">
            <h1 style="font-family: arialembedded, 'ArialEmbedded', sans-serif; font-weight:700;">INVOICE</h1>
            <div class="invoice-no"># {{ $invoice->invoice_number }}</div>
            <table class="meta">
                <tr>
                    <td class="muted">Date:</td>
                    <td class="right">{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                </tr>
            </table>
            <div class="balance">
                <span class="label">Balance Due:</span>
                <span class="amount">$ {{ number_format($invoice->total, 2) }}</span>
                <div style="clear: both;"></div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <div class="label">Bill To:</div>
        @php($billLines = array_values(array_filter(preg_split('/\r?\n/', (string) $invoice->bill_to))))
        @if(count($billLines))
            <div style="font-weight: 700;">{{ $billLines[0] }}</div>
            @if(count($billLines) > 1)
                <div style="white-space: pre-line;">{{ implode("\n", array_slice($billLines, 1)) }}</div>
            @endif
        @else
            <div style="white-space: pre-line;">{{ $invoice->bill_to }}</div>
        @endif
    </div>

    <div class="mt-4">
        <table>
            <thead>
                <tr>
                    <th class="item-header">Item</th>
                    <th class="item-header" style="width: 90px;">Quantity</th>
                    <th class="item-header" style="width: 120px;">Rate</th>
                    <th class="item-header right" style="width: 140px;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $it)
                    <tr>
                        <td>{{ $it->item }}</td>
                        <td class="center">{{ number_format($it->quantity) }}</td>
                        <td class="center">$ {{ number_format($it->rate, 2) }}</td>
                        <td class="money">$ {{ number_format($it->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <table style="width: 240px; float: right; margin-top: 12px;">
        <tr>
            <td class="right muted" style="width: 50%;">Total:</td>
            <td class="money" style="width: 50%;">$ {{ number_format($invoice->total, 2) }}</td>
        </tr>
    </table>
    <div style="clear: both;"></div>

    <div class="mt-4">
        <div class="label">Notes:</div>
        <div style="white-space: pre-line;">{{ (string) $invoice->notes }}</div>
    </div>
</body>
</html>
