<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BinomGoogleSpentData extends Model
{
    use HasFactory;

    protected $table = 'binom_google_spent_data';

    protected $fillable = [
        'name',
        'leads',
        'revenue',
        'date_from',
        'date_to',
        'report_type',
    ];
}
