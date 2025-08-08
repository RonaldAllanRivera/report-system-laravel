<?php

namespace App\Filament\Pages;

use App\Models\BinomRumbleSpentData;
use App\Models\RumbleCampaignData;
use App\Models\RumbleData;
use Filament\Actions;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class RumbleBinomReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.rumble-binom-report';

    protected static ?string $navigationLabel = 'Rumble - Binom Report';

    protected static ?string $navigationGroup = 'Reports';

    public array $filters = [];

    public function mount(): void
    {
        $today = now();
        $yesterday = $today->copy()->subDay()->toDateString();
        $this->filters = [
            'report_type' => 'daily',
            'date_preset' => 'yesterday',
            'date_from' => $yesterday,
            'date_to' => $yesterday,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('filter')
                ->label('Filter')
                ->icon('heroicon-o-funnel')
                ->size('sm')
                ->extraAttributes(['class' => 'w-auto text-xs'])
                ->form([
                    \Filament\Forms\Components\Select::make('report_type')
                        ->label('Report Type')
                        ->options([
                            'daily' => 'Daily (Yesterday)',
                            'weekly' => 'Weekly',
                            'monthly' => 'Monthly',
                        ])->default($this->filters['report_type'] ?? 'daily')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (callable $set, $state) {
                            $today = now();
                            if ($state === 'daily') {
                                $set('date_preset', 'yesterday');
                                $set('date_from', $today->copy()->subDay()->toDateString());
                                $set('date_to', $today->copy()->subDay()->toDateString());
                            } elseif ($state === 'weekly') {
                                $set('date_preset', 'last_7_days');
                                $set('date_from', $today->copy()->subDays(7)->toDateString());
                                $set('date_to', $today->copy()->subDay()->toDateString());
                            } elseif ($state === 'monthly') {
                                $set('date_preset', 'last_month');
                                $set('date_from', $today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString());
                                $set('date_to', $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString());
                            }
                        }),
                    \Filament\Forms\Components\Select::make('date_preset')
                        ->label('Date Preset')
                        ->options([
                            'yesterday' => 'Yesterday',
                            'last_7_days' => 'Last 7 Days',
                            'last_month' => 'Last Month',
                            'custom' => 'Custom Range',
                        ])->default($this->filters['date_preset'] ?? 'yesterday')
                        ->live(),
                    \Filament\Forms\Components\DatePicker::make('date_from')
                        ->label('Date From')
                        ->visible(fn ($get) => $get('date_preset') === 'custom')
                        ->default($this->filters['date_from'] ?? null)
                        ->requiredIf('date_preset', 'custom'),
                    \Filament\Forms\Components\DatePicker::make('date_to')
                        ->label('Date To')
                        ->visible(fn ($get) => $get('date_preset') === 'custom')
                        ->default($this->filters['date_to'] ?? null)
                        ->requiredIf('date_preset', 'custom'),
                ])
                ->action(function (array $data) {
                    // Normalize preset into explicit dates ending at yesterday
                    if (($data['date_preset'] ?? 'yesterday') !== 'custom') {
                        $today = now();
                        switch ($data['date_preset']) {
                            case 'yesterday':
                                $data['date_from'] = $today->copy()->subDay()->toDateString();
                                $data['date_to'] = $data['date_from'];
                                break;
                            case 'last_7_days':
                                $data['date_from'] = $today->copy()->subDays(7)->toDateString();
                                $data['date_to'] = $today->copy()->subDay()->toDateString();
                                break;
                            case 'last_month':
                                $data['date_from'] = $today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
                                $data['date_to'] = $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString();
                                break;
                        }
                    }
                    $this->filters = [
                        'report_type' => $data['report_type'] ?? 'daily',
                        'date_preset' => $data['date_preset'] ?? 'yesterday',
                        'date_from' => $data['date_from'] ?? null,
                        'date_to' => $data['date_to'] ?? null,
                    ];
                }),
        ];
    }

    public function buildReportData(): array
    {
        $df = $this->filters['date_from'] ?? null;
        $dt = $this->filters['date_to'] ?? null;
        $rt = $this->filters['report_type'] ?? 'daily';

        if (!$df || !$dt) {
            $y = now()->subDay()->toDateString();
            $df = $dt = $y;
        }

        // Fetch datasets for the same date range and report type
        $rumble = RumbleData::query()
            ->whereDate('date_from', $df)
            ->whereDate('date_to', $dt)
            ->where('report_type', $rt)
            ->get();

        $campaign = RumbleCampaignData::query()
            ->whereDate('date_from', $df)
            ->whereDate('date_to', $dt)
            ->where('report_type', $rt)
            ->get();

        $binom = BinomRumbleSpentData::query()
            ->whereDate('date_from', $df)
            ->whereDate('date_to', $dt)
            ->where('report_type', $rt)
            ->get();

        // Index helpers
        $id = fn (string $s) => $this->extractId($s);
        $sanitize = fn (?string $s) => $this->sanitizeName($s ?? '');

        // Index Rumble Campaign Data by id and sanitized name
        $campaignById = [];
        $campaignByName = [];
        foreach ($campaign as $rc) {
            $key = $id($rc->name);
            if ($key) $campaignById[$key] = $rc;
            $campaignByName[$sanitize($rc->name)] = $rc;
        }

        // Index Binom by id and sanitized name
        $binomById = [];
        $binomByName = [];
        foreach ($binom as $b) {
            $key = $id($b->name);
            if ($key) $binomById[$key] = $b;
            $binomByName[$sanitize($b->name)] = $b;
        }

        $rows = [];
        foreach ($rumble as $rd) {
            $rdId = $id($rd->campaign);
            $keyName = $sanitize($rd->campaign);

            $rc = $rdId && isset($campaignById[$rdId]) ? $campaignById[$rdId] : ($campaignByName[$keyName] ?? null);
            $b = $rdId && isset($binomById[$rdId]) ? $binomById[$rdId] : ($binomByName[$keyName] ?? null);

            $account = $this->accountName($b?->name ?: $rd->campaign);
            $spend = (float) $rd->spend;
            $revenue = (float) ($b->revenue ?? 0);
            $leads = (int) ($b->leads ?? 0);
            $cpm = (float) $rd->cpm;
            $setCpm = $rc?->cpm !== null ? (float) $rc->cpm : null;
            $dailyCap = $rc?->daily_limit !== null ? (int) $rc->daily_limit : null;

            $rows[] = [
                'account' => $account,
                'campaign_name' => $rd->campaign,
                'daily_cap' => $dailyCap,
                'spend' => $spend,
                'revenue' => $revenue,
                'pl' => $revenue - $spend,
                'roi' => $spend > 0 ? (($revenue / $spend) - 1.0) : null,
                'conversions' => ($revenue > 0 ? max(1, $leads) : ($leads > 0 ? $leads : null)),
                'cpm' => $spend > 0 ? $cpm : null,
                'set_cpm' => $setCpm,
            ];
        }

        // Group by account and compute summaries, then sort groups A-Z and rows A-Z
        $groups = collect($rows)
            ->groupBy('account')
            ->map(function (Collection $items, string $account) {
                // sort rows within the group by campaign name
                $items = $items->sortBy(fn ($r) => strtolower($r['campaign_name']))->values();
                $sumSpend = $items->sum('spend');
                $sumRevenue = $items->sum('revenue');
                return [
                    'account' => $account,
                    'rows' => $items->all(),
                    'summary' => [
                        'spend' => $sumSpend,
                        'revenue' => $sumRevenue,
                        'pl' => $sumRevenue - $sumSpend,
                        'roi' => $sumSpend > 0 ? (($sumRevenue / $sumSpend) - 1.0) : null,
                    ],
                ];
            })
            ->sortBy(fn ($g) => strtolower($g['account']))
            ->values()
            ->all();

        $totalSpend = array_sum(array_column($rows, 'spend'));
        $totalRevenue = array_sum(array_column($rows, 'revenue'));

        return [
            'date_label' => \Illuminate\Support\Carbon::parse($df)->format('m/d'),
            'groups' => $groups,
            'totals' => [
                'spend' => $totalSpend,
                'revenue' => $totalRevenue,
                'pl' => $totalRevenue - $totalSpend,
                'roi' => $totalSpend > 0 ? (($totalRevenue / $totalSpend) - 1.0) : null,
            ],
        ];
    }

    protected function extractId(string $s): ?string
    {
        if (preg_match('/(\d{6}_\d{2})/', $s, $m)) {
            return $m[1];
        }
        if (preg_match('/(\d{6})/', $s, $m)) {
            return $m[1];
        }
        return null;
    }

    protected function sanitizeName(string $s): string
    {
        // Remove trailing domain in parentheses and normalize spaces
        $s = preg_replace('/\s*\([^)]*\)\s*$/', '', $s);
        return trim(preg_replace('/\s+/', ' ', $s));
    }

    protected function accountName(string $full): string
    {
        $full = trim($full);
        $clean = $this->sanitizeName($full);
        $parts = explode(' - ', $clean);
        return trim($parts[0] ?? $clean);
    }

    public function fmtMoney(?float $v): string
    {
        if ($v === null) return '';
        $sign = $v < 0 ? '-' : '';
        return $sign . '$' . number_format(abs($v), 2);
    }

    public function fmtPercent(?float $v): string
    {
        if ($v === null) return '';
        return number_format($v * 100, 2) . '%';
    }
}
