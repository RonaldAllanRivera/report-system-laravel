<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RumbleData extends Model
{
    protected $fillable = [
        'campaign',
        'spend',
        'cpm',
        'date_from',
        'date_to',
    ];
}
