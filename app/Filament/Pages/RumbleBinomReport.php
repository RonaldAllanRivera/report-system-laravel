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

    protected static ?string $navigationLabel = '4. Rumble Binom Report';

    protected static ?string $navigationGroup = 'Rumble and Binom Reports Only';
    protected static ?int $navigationSort = 4;

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
        // Filter button removed in favor of inline report type tabs.
        return [];
    }

    public function setReportType(string $type): void
    {
        $type = in_array($type, ['daily', 'weekly', 'monthly'], true) ? $type : 'daily';
        $this->filters['report_type'] = $type;
    }

    /**
     * Build combined report data for a specific date range & report type.
     */
    public function buildReportDataFor(string $df, string $dt, string $rt): array
    {
        // normalized inputs expected (strings: Y-m-d)

        // Fetch datasets for the same date range and report type
        $rumble = RumbleData::query()
            ->where('date_from', $df)
            ->where('date_to', $dt)
            ->where('report_type', $rt)
            ->get();

        $campaign = RumbleCampaignData::query()
            ->where('date_from', $df)
            ->where('date_to', $dt)
            ->where('report_type', $rt)
            ->get();

        $binom = BinomRumbleSpentData::query()
            ->where('date_from', $df)
            ->where('date_to', $dt)
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
        $seen = [];
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

            // mark this campaign as seen (prefer id, fallback to sanitized name)
            $seen[$rdId ?: $keyName] = true;
        }

        // Add Binom-only rows (revenue is most important) when no matching Rumble entry exists
        foreach ($binom as $b) {
            $rev = (float) ($b->revenue ?? 0);
            if ($rev <= 0) {
                continue; // safety guard, import already skips <= 0
            }
            $bid = $id($b->name ?? '');
            $bKeyName = $sanitize($b->name ?? '');
            $key = $bid ?: $bKeyName;
            if (isset($seen[$key])) {
                continue; // already represented by a Rumble row
            }

            $rc = $bid && isset($campaignById[$bid]) ? $campaignById[$bid] : ($campaignByName[$bKeyName] ?? null);

            $account = $this->accountName($b->name);
            $leads = (int) ($b->leads ?? 0);
            $setCpm = $rc?->cpm !== null ? (float) $rc->cpm : null;
            $dailyCap = $rc?->daily_limit !== null ? (int) $rc->daily_limit : null;

            $rows[] = [
                'account' => $account,
                'campaign_name' => $b->name,
                'daily_cap' => $dailyCap,
                'spend' => 0.0,
                'revenue' => $rev,
                'pl' => $rev,
                'roi' => null,
                'conversions' => max(1, $leads),
                'cpm' => null,
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
            'date_from' => $df,
            'date_to' => $dt,
            'report_type' => $rt,
            'date_label' => \Illuminate\Support\Carbon::parse($df)->format('F j, Y'),
            'groups' => $groups,
            'totals' => [
                'spend' => $totalSpend,
                'revenue' => $totalRevenue,
                'pl' => $totalRevenue - $totalSpend,
                'roi' => $totalSpend > 0 ? (($totalRevenue / $totalSpend) - 1.0) : null,
            ],
            // presence flags
            'has_rumble' => $rumble->count() > 0,
            'has_rows' => count($rows) > 0,
        ];
    }

    /**
     * Backwards-compatible: build based on current filters.
     */
    public function buildReportData(): array
    {
        $df = $this->filters['date_from'] ?? null;
        $dt = $this->filters['date_to'] ?? null;
        $rt = $this->filters['report_type'] ?? 'daily';

        if (!$df || !$dt) {
            $y = now()->subDay()->toDateString();
            $df = $dt = $y;
        }

        return $this->buildReportDataFor($df, $dt, $rt);
    }

    /**
     * Returns an array of grouped sections keyed by upload date and report_type.
     */
    public function buildGroupedByUploadDate(): array
    {
        $rt = $this->filters['report_type'] ?? 'daily';

        // Find distinct upload batches per day and date range to avoid mixing multiple uploads on same day
        $batches = RumbleData::query()
            ->selectRaw('date_from as df, date_to as dt, report_type as rt, COUNT(*) as c')
            ->when($rt, fn ($q) => $q->where('report_type', $rt))
            ->groupBy('df', 'dt', 'rt')
            ->orderByDesc('df')
            ->orderByDesc('dt')
            ->get();

        $sections = [];
        foreach ($batches as $batch) {
            $df = (string) $batch->df;
            $dt = (string) $batch->dt;
            $brt = (string) ($batch->rt ?? $rt);
            $sections[] = [
                'date_from' => $df,
                'date_to' => $dt,
                'report_type' => $brt,
                'count' => (int) $batch->c,
                'report' => $this->buildReportDataFor($df, $dt, $brt),
            ];
        }

        return $sections;
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
