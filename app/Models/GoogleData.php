<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleData extends Model
{
    protected $table = 'google_data';

    protected $fillable = [
        'account_name',
        'campaign',
        'cost',
        'date_from',
        'date_to',
        'report_type', // weekly | monthly
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'date_from' => 'date',
        'date_to' => 'date',
        'report_type' => 'string',
    ];
}
