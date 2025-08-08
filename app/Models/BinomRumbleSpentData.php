<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BinomRumbleSpentData extends Model
{
    use HasFactory;

    protected $table = 'binom_rumble_spent_data';

    protected $fillable = [
        'name',
        'leads',
        'revenue',
        'date_from',
        'date_to',
        'report_type',
    ];

    protected $casts = [
        'leads' => 'integer',
        'revenue' => 'decimal:2',
        'date_from' => 'date',
        'date_to' => 'date',
    ];
}
