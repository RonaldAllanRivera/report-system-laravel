<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'bill_to',
        'invoice_number',
        'invoice_date',
        'notes',
        'payment_link',
        'total',
    ];

    protected $casts = [
        'invoice_date' => 'date',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function computeTotal(): float
    {
        return (float) $this->items->sum('amount');
    }
}
