<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function download(Invoice $invoice)
    {
        $invoice->load('items');
        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
        ])->setPaper('A4');

        $fileName = trim(($invoice->name ?: 'Invoice')) . ' - ' . $invoice->invoice_number . '.pdf';
        return $pdf->download($fileName);
    }
}
