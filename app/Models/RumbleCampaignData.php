<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RumbleCampaignData extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'cpm',
        'daily_limit',
        'date_from',
        'date_to',
        'report_type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'cpm' => 'decimal:2',
        'daily_limit' => 'integer',
        'date_from' => 'date',
        'date_to' => 'date',
    ];

    /**
     * Get the report type options.
     *
     * @return array
     */
    public static function getReportTypeOptions(): array
    {
        return [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
        ];
    }
}
