<?php

namespace App\Filament\Pages;

use App\Models\GoogleData;
use App\Models\BinomGoogleSpentData;
use Filament\Actions;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class GoogleBinomReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.google-binom-report';

    protected static ?string $navigationLabel = '3. Google Binom Report';

    protected static ?string $navigationGroup = 'Google and Binom Reports Only';
    protected static ?int $navigationSort = 3;

    public array $filters = [];

    public function mount(): void
    {
        $today = now();
        $yesterday = $today->copy()->subDay()->toDateString();
        // Default to weekly, last 7 days ending yesterday
        $this->filters = [
            'report_type' => 'weekly',
            'date_preset' => 'last_7_days',
            'date_from' => $today->copy()->subDays(7)->toDateString(),
            'date_to' => $yesterday,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function setReportType(string $type): void
    {
        $type = in_array($type, ['weekly', 'monthly'], true) ? $type : 'weekly';
        $this->filters['report_type'] = $type;
    }

    /**
     * Build Google-Binom report data for a specific date range & report type.
     */
    public function buildReportDataFor(string $df, string $dt, string $rt, bool $withPrev = true): array
    {
        // Fetch datasets for the same date range and report type
        $google = GoogleData::query()
            ->where('date_from', $df)
            ->where('date_to', $dt)
            ->where('report_type', $rt)
            ->get();

        $binom = BinomGoogleSpentData::query()
            ->where('date_from', $df)
            ->where('date_to', $dt)
            ->where('report_type', $rt)
            ->get();

        // Index helpers
        $id = fn (string $s) => $this->extractId($s);
        $sanitize = fn (?string $s) => $this->sanitizeName($s ?? '');
        $base = fn (?string $s) => $this->baseName($s ?? '');

        // Index Binom by id, sanitized name, and base name. Store lists to support picking unused.
        $binomById = [];
        $binomByName = [];
        $binomByBase = [];
        foreach ($binom as $b) {
            $key = $id($b->name ?? '');
            if ($key) { $binomById[$key] = $binomById[$key] ?? []; $binomById[$key][] = $b; }
            $nm = $sanitize($b->name ?? '');
            $binomByName[$nm] = $binomByName[$nm] ?? []; $binomByName[$nm][] = $b;
            $bkey = $base($b->name ?? '');
            if ($bkey !== '') { $binomByBase[$bkey] = $binomByBase[$bkey] ?? []; $binomByBase[$bkey][] = $b; }
        }

        $rows = [];
        $usedBinom = [];
        $pick = function (string $gdId, string $keyName, string $keyBase) use (&$usedBinom, $binomById, $binomByName, $binomByBase) {
            // 1) Exact ID
            if ($gdId !== '' && isset($binomById[$gdId])) {
                foreach ($binomById[$gdId] as $cand) {
                    $cid = (int) ($cand->id ?? 0);
                    if (!$cid || isset($usedBinom[$cid])) continue;
                    $usedBinom[$cid] = true;
                    return $cand;
                }
            }
            // If Google row has an ID but we couldn't match by ID, do not fall back to name/base/substring.
            // This avoids cross-campaign matches that consume unrelated Binom rows.
            if ($gdId !== '') {
                return null;
            }
            // 2) Sanitized name
            if ($keyName !== '' && isset($binomByName[$keyName])) {
                foreach ($binomByName[$keyName] as $cand) {
                    $cid = (int) ($cand->id ?? 0);
                    if (!$cid || isset($usedBinom[$cid])) continue;
                    $usedBinom[$cid] = true;
                    return $cand;
                }
            }
            // 3) Base/substring fallback intentionally disabled for Google-Binom report.
            // We only allow sanitized-name exact matches when Google lacks an ID.
            return null;
        };

        foreach ($google as $gd) {
            $gdId = $id($gd->campaign ?? '');
            $keyName = $sanitize($gd->campaign ?? '');
            $keyBase = $base($gd->campaign ?? '');

            // Select first unused candidate by priority
            $b = $pick($gdId ?: '', $keyName, $keyBase);
            // Prefer the Google account name when available so rows appear under the expected account group.
            $googleAccount = trim((string) ($gd->account_name ?? ''));
            $account = $googleAccount !== ''
                ? $googleAccount
                : ($b ? $this->accountName($b->name ?? '') : '');
            $spend = (float) ($gd->cost ?? 0);
            $revenue = (float) ($b->revenue ?? 0);
            $leads = (int) ($b->leads ?? 0);

            $rows[] = [
                'account' => $account,
                'campaign_name' => (string) ($gd->campaign ?? ''),
                'spend' => $spend,
                'revenue' => $revenue,
                'pl' => $revenue - $spend,
                'roi' => $spend > 0 ? (($revenue / $spend) - 1.0) : null,
                'roi_prev' => null, // to be filled later
                'sales' => ($revenue > 0 ? max(1, $leads) : ($leads > 0 ? $leads : null)),
                // keys for lookups
                '_key' => $gdId ?: $keyName,
            ];

        }

        // Add Binom-only rows for any Binom entries not matched yet. Include only revenue>0 OR leads>0
        foreach ($binom as $b) {
            $rev = (float) ($b->revenue ?? 0);
            $cid = (int) ($b->id ?? 0);
            if ($cid && isset($usedBinom[$cid])) continue; // already consumed by a Google row
            if ($rev <= 0) {
                // include pure leads-only only if leads > 0, otherwise skip entirely
                if ((int) ($b->leads ?? 0) <= 0) continue;
            }

            $rows[] = [
                'account' => $this->accountName($b->name ?? ''),
                'campaign_name' => (string) ($b->name ?? ''), // fallback since no Google row
                'spend' => 0.0,
                'revenue' => $rev,
                'pl' => $rev,
                'roi' => null,
                'roi_prev' => null,
                'sales' => max(1, (int) ($b->leads ?? 0)),
                '_key' => ($id($b->name ?? '') ?: $sanitize($b->name ?? '')),
            ];
        }

        // Compute previous period ROI map for 'ROI LAST WEEK/MONTH' only when requested
        if ($withPrev) {
            $prevDf = $df;
            $prevDt = $dt;
            if ($rt === 'weekly') {
                $prevDf = (string) \Illuminate\Support\Carbon::parse($df)->subDays(7)->toDateString();
                $prevDt = (string) \Illuminate\Support\Carbon::parse($dt)->subDays(7)->toDateString();
            } elseif ($rt === 'monthly') {
                $prevDf = (string) \Illuminate\Support\Carbon::parse($df)->subMonthNoOverflow()->startOfMonth()->toDateString();
                $prevDt = (string) \Illuminate\Support\Carbon::parse($df)->subMonthNoOverflow()->endOfMonth()->toDateString();
            }
            $prevMap = $this->buildRoiMapFor($prevDf, $prevDt, $rt);
            foreach ($rows as &$row) {
                $k = (string) ($row['_key'] ?? '');
                if ($k !== '' && array_key_exists($k, $prevMap)) {
                    $row['roi_prev'] = $prevMap[$k];
                } else {
                    $row['roi_prev'] = null;
                }
            }
            unset($row);
        }

        // Group by account and compute summaries, then sort
        $groups = collect($rows)
            ->groupBy('account')
            ->map(function (Collection $items, string $account) {
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
            'groups' => $groups,
            'totals' => [
                'spend' => $totalSpend,
                'revenue' => $totalRevenue,
                'pl' => $totalRevenue - $totalSpend,
                'roi' => $totalSpend > 0 ? (($totalRevenue / $totalSpend) - 1.0) : null,
            ],
            'has_rows' => count($rows) > 0,
        ];
    }

    public function buildReportData(): array
    {
        $df = $this->filters['date_from'] ?? null;
        $dt = $this->filters['date_to'] ?? null;
        $rt = $this->filters['report_type'] ?? 'weekly';

        if (!$df || !$dt) {
            $y = now()->subDay()->toDateString();
            $df = $dt = $y;
        }

        return $this->buildReportDataFor($df, $dt, $rt);
    }

    /**
     * Returns grouped sections for all batches (unique date_from/date_to) for selected report type,
     * based on union of google_data and binom_google_spent_data uploads.
     */
    public function buildGroupedByUploadDate(): array
    {
        $rt = $this->filters['report_type'] ?? 'weekly';

        $gBatches = GoogleData::query()
            ->selectRaw('date_from as df, date_to as dt, report_type as rt, COUNT(*) as c')
            ->when($rt, fn ($q) => $q->where('report_type', $rt))
            ->groupBy('df', 'dt', 'rt')
            ->get()
            ->map(fn ($b) => ['df' => (string) $b->df, 'dt' => (string) $b->dt, 'rt' => (string) $b->rt, 'c' => (int) $b->c]);

        $bBatches = BinomGoogleSpentData::query()
            ->selectRaw('date_from as df, date_to as dt, report_type as rt, COUNT(*) as c')
            ->when($rt, fn ($q) => $q->where('report_type', $rt))
            ->groupBy('df', 'dt', 'rt')
            ->get()
            ->map(fn ($b) => ['df' => (string) $b->df, 'dt' => (string) $b->dt, 'rt' => (string) $b->rt, 'c' => (int) $b->c]);

        $all = collect([])->merge($gBatches)->merge($bBatches)
            ->unique(fn ($b) => $b['df'].'|'.$b['dt'].'|'.$b['rt'])
            ->sortByDesc(fn ($b) => $b['df'])
            ->sortByDesc(fn ($b) => $b['dt'])
            ->values()
            ->all();

        $sections = [];
        foreach ($all as $batch) {
            $df = (string) $batch['df'];
            $dt = (string) $batch['dt'];
            $brt = (string) ($batch['rt'] ?? $rt);
            // Approximate count: sum of counts for this key from both sources
            $count = (int) collect([$gBatches, $bBatches])
                ->flatten(1)
                ->filter(fn ($x) => $x['df'] === $df && $x['dt'] === $dt && $x['rt'] === $brt)
                ->sum('c');

            $sections[] = [
                'date_from' => $df,
                'date_to' => $dt,
                'report_type' => $brt,
                'count' => $count,
                'report' => $this->buildReportDataFor($df, $dt, $brt),
            ];
        }

        return $sections;
    }

    /**
     * Build a map of key => ROI for a previous period, to support ROI LAST WEEK/MONTH lookup.
     */
    protected function buildRoiMapFor(string $df, string $dt, string $rt): array
    {
        // Build a lightweight ROI map from raw tables for the given period.
        $google = \App\Models\GoogleData::query()
            ->select(['campaign', 'cost'])
            ->where('date_from', $df)
            ->where('date_to', $dt)
            ->where('report_type', $rt)
            ->get();

        $binom = \App\Models\BinomGoogleSpentData::query()
            ->select(['name', 'revenue', 'leads'])
            ->where('date_from', $df)
            ->where('date_to', $dt)
            ->where('report_type', $rt)
            ->get();

        $id = fn (string $s) => $this->extractId($s);
        $sanitize = fn (?string $s) => $this->sanitizeName($s ?? '');
        $base = fn (?string $s) => $this->baseName($s ?? '');

        $binomById = [];
        $binomByName = [];
        $binomByBase = [];
        foreach ($binom as $b) {
            $key = $id($b->name ?? '') ?? '';
            if ($key !== '') $binomById[$key] = $b;
            $binomByName[$sanitize($b->name ?? '')] = $b;
            $bkey = $base($b->name ?? '');
            if ($bkey !== '') $binomByBase[$bkey] = $b;
        }

        $map = [];
        foreach ($google as $gd) {
            $gdId = $id($gd->campaign ?? '') ?? '';
            $keyName = $sanitize($gd->campaign ?? '');
            $keyBase = $base($gd->campaign ?? '');

            $b = $gdId !== '' && isset($binomById[$gdId]) ? $binomById[$gdId] : ($binomByName[$keyName] ?? null);
            if (!$b) {
                $b = $binomByBase[$keyBase] ?? null;
            }
            if (!$b && $keyBase !== '') {
                foreach ($binomByBase as $bkey => $candidate) {
                    if (str_contains($bkey, $keyBase) || str_contains($keyBase, $bkey)) {
                        $b = $candidate;
                        break;
                    }
                }
            }

            $spend = (float) ($gd->cost ?? 0);
            $rev = (float) ($b->revenue ?? 0);
            $key = $gdId !== '' ? $gdId : $keyName;
            if ($key !== '') {
                $map[$key] = $spend > 0 ? (($rev / $spend) - 1.0) : null;
            }
        }

        return $map;
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

    protected function baseName(string $s): string
    {
        $s = $this->sanitizeName($s);
        $s = preg_replace('/\s*-\s*\d{6}(?:_\d{2})?.*$/', '', $s);
        $s = preg_replace('/\s*-\s*[A-Z]{2}\s*$/', '', $s);
        return trim($s);
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
