<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'item',
        'quantity',
        'rate',
        'amount',
    ];

    protected $casts = [
        'quantity' => 'float',
        'rate' => 'float',
        'amount' => 'float',
    ];

    protected static function booted(): void
    {
        static::saving(function (InvoiceItem $item) {
            // Keep amount consistent with quantity * rate
            $qty = (float) ($item->quantity ?? 0);
            $rate = (float) ($item->rate ?? 0);
            $item->amount = round($qty * $rate, 2);
        });

        $recompute = function (InvoiceItem $item) {
            $invoice = $item->invoice;
            if ($invoice) {
                $invoice->update(['total' => $invoice->computeTotal()]);
            }
        };

        static::saved($recompute);
        static::deleted($recompute);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
