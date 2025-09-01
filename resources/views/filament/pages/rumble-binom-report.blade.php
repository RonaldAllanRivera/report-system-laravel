@php
    $sections = $this->buildGroupedByUploadDate();
    $filters = $this->filters;
@endphp

<x-filament-panels::page>
    <div class="space-y-3">
        <div class="flex items-center justify-end">
            @php($rt = $filters['report_type'] ?? 'daily')
            <div class="inline-flex divide-x divide-gray-200 dark:divide-gray-700 rounded-md shadow-sm overflow-hidden ring-1 ring-gray-200 dark:ring-gray-700 bg-white dark:bg-gray-900"
                role="group">
                <button type="button" wire:click="setReportType('daily')"
                    class="px-3 py-1.5 text-xs font-medium focus:outline-none transition
                    {{ $rt === 'daily' ? 'bg-amber-500 text-white' : 'bg-gray-50 text-gray-800 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }} rounded-l-md">
                    Daily
                </button>
                <button type="button" wire:click="setReportType('weekly')"
                    class="px-3 py-1.5 text-xs font-medium focus:outline-none transition
                    {{ $rt === 'weekly' ? 'bg-amber-500 text-white' : 'bg-gray-50 text-gray-800 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }}">
                    Weekly
                </button>
                <button type="button" wire:click="setReportType('monthly')"
                    class="px-3 py-1.5 text-xs font-medium focus:outline-none transition
                    {{ $rt === 'monthly' ? 'bg-amber-500 text-white' : 'bg-gray-50 text-gray-800 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }} rounded-r-md">
                    Monthly
                </button>
            </div>
        </div>
        @forelse($sections as $section)
            @php($report = $section['report'])
            <div x-data="{ open: false, copying: false, creating: false, drafting: false }" class="rounded-lg border bg-white"
                wire:key="section-{{ $section['report_type'] }}-{{ $section['date_from'] }}-{{ $section['date_to'] }}">
                <div @click="open = !open" class="flex items-center justify-between px-4 py-3 cursor-pointer">
                    <div class="font-medium text-gray-700">
                        {{ \Illuminate\Support\Carbon::parse($section['date_from'])->format('F j, Y') }} —
                        {{ \Illuminate\Support\Carbon::parse($section['date_to'])->format('F j, Y') }}
                        <span
                            class="ml-2 text-[10px] rounded bg-gray-100 px-1.5 py-0.5 text-gray-600">{{ strtoupper($section['report_type'] ?? ($report['report_type'] ?? '')) }}</span>
                        @php($visibleCount = 0)
                        @foreach (($report['groups'] ?? []) as $g)
                            @foreach (($g['rows'] ?? []) as $r)
                                @php($visibleCount += ((float)($r['spend'] ?? 0) === 0.0 && (float)($r['revenue'] ?? 0) === 0.0) ? 0 : 1)
                            @endforeach
                        @endforeach
                        <span class="text-xs text-gray-500">({{ $visibleCount }} entries)</span>
                    </div>
                    <div class="text-sm text-gray-600 flex items-center gap-2">
                        <span class="font-semibold">{{ $this->fmtMoney($report['totals']['spend'] ?? 0) }} spent</span>
                        <span class="text-gray-400">·</span>
                        <span class="font-semibold">{{ $this->fmtMoney($report['totals']['revenue'] ?? 0) }}
                            revenue</span>
                        <button type="button"
                            @click.stop="RB_copyTable($refs.tbl, '{{ ($section['report_type'] ?? 'daily') === 'daily' ? \Illuminate\Support\Carbon::parse($section['date_to'])->format('d/m') : (\Illuminate\Support\Carbon::parse($section['date_from'])->format('d/m') . ' - ' . \Illuminate\Support\Carbon::parse($section['date_to'])->format('d/m')) }}'); copying = true; setTimeout(()=>copying=false, 1200)"
                            title="Copy this table to clipboard"
                            class="ml-2 text-red-600 hover:text-red-700 font-semibold text-xs uppercase tracking-wide {{ $report['has_rows'] ? '' : 'opacity-40 pointer-events-none' }}">
                            <span x-show="!copying">COPY TABLE</span>
                            <span x-show="copying" class="text-green-600">COPIED</span>
                        </button>
                        <button type="button"
                            @click.stop="creating=true; RB_createSheet($refs.tbl,
                              '{{ ($section['report_type'] ?? 'daily') === 'daily' ? \Illuminate\Support\Carbon::parse($section['date_to'])->format('d/m') : (\Illuminate\Support\Carbon::parse($section['date_from'])->format('d/m') . ' - ' . \Illuminate\Support\Carbon::parse($section['date_to'])->format('d/m')) }}',
                              '{{ $section['report_type'] ?? 'daily' }}',
                              '{{ $section['date_to'] }}'
                            ).finally(()=>creating=false)"
                            title="Create a Google Sheet with this table"
                            class="ml-2 text-blue-600 hover:text-blue-700 font-semibold text-xs uppercase tracking-wide {{ $report['has_rows'] ? '' : 'opacity-40 pointer-events-none' }}">
                            <span x-show="!creating">CREATE SHEET</span>
                            <span x-show="creating" class="text-green-600">CREATING…</span>
                        </button>
                        <button type="button"
                            @click.stop="drafting=true; RB_createDraft($refs.tbl,
                              '{{ ($section['report_type'] ?? 'daily') === 'daily' ? \Illuminate\Support\Carbon::parse($section['date_to'])->format('d/m') : (\Illuminate\Support\Carbon::parse($section['date_from'])->format('d/m') . ' - ' . \Illuminate\Support\Carbon::parse($section['date_to'])->format('d/m')) }}',
                              '{{ $section['report_type'] ?? 'daily' }}',
                              '{{ $section['date_to'] }}',
                              '{{ $section['date_from'] }}'
                            ).finally(()=>drafting=false)"
                            title="Create a Gmail draft with this table"
                            class="ml-2 text-green-700 hover:text-green-800 font-semibold text-xs uppercase tracking-wide {{ $report['has_rows'] ? '' : 'opacity-40 pointer-events-none' }}">
                            <span x-show="!drafting">CREATE DRAFT</span>
                            <span x-show="drafting" class="text-green-600">CREATING…</span>
                        </button>
                        <svg x-bind:class="open ? 'rotate-180' : ''" class="h-4 w-4 transition-transform"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>
                <div x-show="open" x-collapse class="overflow-x-auto">
                    @if ($report['has_rows'])
                        <table x-ref="tbl" class="min-w-full text-xs">
                            <thead style="background-color: #dadada;">
                                <tr class="text-left">
                                    <th class="px-3 py-2 font-medium text-gray-600">ACCOUNT NAME</th>
                                    <th class="px-3 py-2 font-medium text-gray-600">CAMPAIGN NAME</th>
                                    <th class="px-3 py-2 font-medium text-gray-600">DAILY CAP</th>
                                    <th class="px-3 py-2 font-medium text-gray-600">SPEND</th>
                                    <th class="px-3 py-2 font-medium text-gray-600">REVENUE</th>
                                    <th class="px-3 py-2 font-medium text-gray-600">P/L</th>
                                    <th class="px-3 py-2 font-medium text-gray-600">ROI</th>
                                    <th class="px-3 py-2 font-medium text-gray-600">CONVERSIONS</th>
                                    <th class="px-3 py-2 font-medium text-gray-600">CPM</th>
                                    <th class="px-3 py-2 font-medium text-gray-600">SET CPM</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($report['groups'] as $group)
                                    @php($hasVisibleRow = false)
                                    @foreach ($group['rows'] as $row)
                                        @php($sp = (float)($row['spend'] ?? 0))
                                        @php($rv = (float)($row['revenue'] ?? 0))
                                        @continue($sp === 0.0 && $rv === 0.0)
                                        @php($hasVisibleRow = true)
                                        <tr class="border-t">
                                            <td class="px-3 py-2 text-gray-600">{{ $row['account'] }}</td>
                                            <td class="px-3 py-2 text-gray-600">{{ $row['campaign_name'] }}</td>
                                            <td class="px-3 py-2 text-gray-600">
                                                {{ $this->fmtMoney($row['daily_cap'] ?? 0) }}</td>
                                            <td class="px-3 py-2 text-gray-600">{{ $this->fmtMoney($row['spend']) }}
                                            </td>
                                            <td class="px-3 py-2 text-gray-600">{{ $this->fmtMoney($row['revenue']) }}
                                            </td>
                                            <td class="px-3 py-2 text-gray-600"
                                                style="{{ ($row['pl'] ?? 0) > 0 ? 'background-color:#a3da9d' : (($row['pl'] ?? 0) < 0 ? 'background-color:#ff8080' : '') }}">
                                                {{ $this->fmtMoney($row['pl']) }}</td>
                                            <td class="px-3 py-2 text-gray-600"
                                                style="{{ ($row['roi'] ?? 0) > 0 ? 'background-color:#a3da9d' : (($row['roi'] ?? 0) < 0 ? 'background-color:#ff8080' : '') }}">
                                                {{ $this->fmtPercent($row['roi']) }}</td>
                                            <td class="px-3 py-2 text-gray-600">{{ $row['conversions'] ?? '' }}</td>
                                            <td class="px-3 py-2 text-gray-600">
                                                {{ $row['cpm'] !== null ? $this->fmtMoney($row['cpm']) : '' }}</td>
                                            <td class="px-3 py-2 text-gray-600">
                                                {{ $row['set_cpm'] !== null ? $this->fmtMoney($row['set_cpm']) : '' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    @if ($hasVisibleRow)
                                        <tr class="border-t bg-gray-50 font-semibold">
                                            <td class="px-3 py-2 text-gray-700">{{ $group['account'] }}</td>
                                            <td class="px-3 py-2 italic text-gray-700">Account Summary</td>
                                            <td class="px-3 py-2"></td>
                                            <td class="px-3 py-2 text-gray-700">
                                                {{ $this->fmtMoney($group['summary']['spend'] ?? 0) }}</td>
                                            <td class="px-3 py-2 text-gray-700">
                                                {{ $this->fmtMoney($group['summary']['revenue'] ?? 0) }}</td>
                                            <td class="px-3 py-2 text-gray-700"
                                                style="{{ ($group['summary']['pl'] ?? 0) > 0 ? 'background-color:#a3da9d' : (($group['summary']['pl'] ?? 0) < 0 ? 'background-color:#ff8080' : '') }}">
                                                {{ $this->fmtMoney($group['summary']['pl'] ?? 0) }}</td>
                                            @php($gRoi = $group['summary']['roi'] ?? null)
                                            <td class="px-3 py-2 text-gray-700"
                                                style="{{ ($gRoi ?? 0) > 0 ? 'background-color:#a3da9d' : (($gRoi ?? 0) < 0 ? 'background-color:#ff8080' : '') }}">
                                                {{ $this->fmtPercent($group['summary']['roi'] ?? null) }}</td>
                                            <td class="px-3 py-2"></td>
                                            <td class="px-3 py-2"></td>
                                            <td class="px-3 py-2"></td>
                                        </tr>
                                        <tr class="border-0">
                                            <td colspan="10" class="px-3 py-2"></td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr class="border-t font-semibold">
                                    <td class="px-3 py-2 text-gray-700"></td>
                                    <td class="px-3 py-2 italic text-gray-700">SUMMARY</td>
                                    <td class="px-3 py-2"></td>
                                    <td class="px-3 py-2 text-gray-700">
                                        {{ $this->fmtMoney($report['totals']['spend'] ?? 0) }}</td>
                                    <td class="px-3 py-2 text-gray-700">
                                        {{ $this->fmtMoney($report['totals']['revenue'] ?? 0) }}</td>
                                    <td class="px-3 py-2 text-gray-700"
                                        style="{{ ($report['totals']['pl'] ?? 0) > 0 ? 'background-color:#a3da9d' : (($report['totals']['pl'] ?? 0) < 0 ? 'background-color:#ff8080' : '') }}">
                                        {{ $this->fmtMoney($report['totals']['pl'] ?? 0) }}</td>
                                    @php($tRoi = $report['totals']['roi'] ?? null)
                                    <td class="px-3 py-2 text-gray-700"
                                        style="{{ ($tRoi ?? 0) > 0 ? 'background-color:#a3da9d' : (($tRoi ?? 0) < 0 ? 'background-color:#ff8080' : '') }}">
                                        {{ $this->fmtPercent($report['totals']['roi'] ?? null) }}</td>
                                    <td class="px-3 py-2"></td>
                                    <td class="px-3 py-2"></td>
                                    <td class="px-3 py-2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    @else
                        <div class="px-4 py-3 text-xs text-gray-600">No data for this date range.</div>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-lg border bg-white p-4 text-sm text-gray-600">No uploads found for the selected report
                type.</div>
        @endforelse
    </div>

    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <h2 class="text-lg font-semibold">Rumble Binom Report</h2>
                <p class="text-xs text-gray-600">Type: <span
                        class="font-medium">{{ strtoupper($filters['report_type'] ?? 'daily') }}</span></p>
            </div>

            <div class="flex items-center gap-2"></div>
        </div>
    </x-slot>
</x-filament-panels::page>

<script>
    window.RB_copyTable = window.RB_copyTable || function(table, dateStr) {
        if (!table) return;

        const headRow = table.querySelector('thead tr');
        const colCount = headRow ? Array.from(headRow.cells).reduce((a, c) => a + (c.colSpan || 1), 0) : 10;
        const rows = Array.from(table.querySelectorAll('thead tr, tbody tr, tfoot tr'));
        const moneyToNum = s => (s || '').replace(/[^0-9.\-]/g, '');
        const escapeHtml = (s) => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');

        const headHtmlRows = [];
        const bodyHtmlRows = [];
        const footHtmlRows = [];

        // Track per-account ranges and account summary rows
        let groupStartRow = null;
        let groupEndRow = null;
        const accountSummaryRows = [];

        // Add date row at the top
        const dateRow = new Array(colCount).fill('');
        dateRow[0] = dateStr || '';
        const dateHtmlRow =
            `<tr><td colspan="${colCount}" style="text-align:left;font-weight:bold;"><b>${escapeHtml(dateStr || '')}</b></td></tr>`;
        headHtmlRows.push(dateHtmlRow);

        const lines = [dateRow.join('\t')].concat(rows.map((tr, idx) => {
            const rowNumber = idx + 2; // date row is 1, header is 2, data starts at 3
            const isHead = tr.parentElement && tr.parentElement.tagName.toLowerCase() === 'thead';
            const cells = Array.from(tr.cells);
            const parentTag = tr.parentElement ? tr.parentElement.tagName.toLowerCase() : '';
            const secondText = (cells[1] ? (cells[1].innerText || '') : '').replace(/\s+/g, ' ').trim();
            const isAccountSummary = !isHead && parentTag === 'tbody' && secondText.toLowerCase() ===
                'account summary';
            const isSpacer = !isHead && parentTag === 'tbody' && cells.length === 1 && ((cells[0]
                .colSpan || 1) >= (headRow ? headRow.cells.length : 10));
            const isFoot = parentTag === 'tfoot';
            const isSummaryRow = isFoot && secondText.toLowerCase() === 'summary';
            const isDataRow = !isHead && !isAccountSummary && !isSpacer && !isFoot;

            if (isDataRow) {
                if (groupStartRow === null) groupStartRow = rowNumber;
                groupEndRow = rowNumber;
            }
            if (isAccountSummary) {
                accountSummaryRows.push(rowNumber);
            }

            const out = new Array(colCount).fill('');
            const boldRow = (isAccountSummary || isSummaryRow);
            let htmlRow = `<tr${boldRow ? ' style="font-weight:600;"' : ''}>`;
            const htmlCells = [];
            let ci = 0;

            cells.forEach(td => {
                const span = td.colSpan || 1;
                const raw = (td.innerText || '').replace(/\s+/g, ' ').trim();
                const tdBg = (td && td.style && td.style.backgroundColor) ? td.style
                    .backgroundColor : '';

                for (let i = 0; i < span && ci < colCount; i++) {
                    if (i === 0) {
                        let val = raw;
                        if (!isHead) {
                            const col = ci + 1;
                            // Normalize numeric $ columns
                            if (col === 3 || col === 4 || col === 5 || col === 9 || col ===
                                10) {
                                val = moneyToNum(val) || '';
                            }
                            // P/L formula (col 6)
                            if (col === 6) {
                                val = raw ? `=E${rowNumber}-D${rowNumber}` : '';
                            }
                            // ROI formula (col 7)
                            if (col === 7) {
                                val = raw ?
                                    `=IF(D${rowNumber}>0, TEXT((E${rowNumber}/D${rowNumber})-1, "0.00%"), "")` :
                                    '';
                            }
                            // Account Summary formulas
                            if (isAccountSummary && groupStartRow !== null && groupEndRow !==
                                null) {
                                if (col === 4) {
                                    val = `=SUM(D${groupStartRow}:D${groupEndRow})`;
                                }
                                if (col === 5) {
                                    val = `=SUM(E${groupStartRow}:E${groupEndRow})`;
                                }
                            }
                            // Bottom SUMMARY row formulas
                            if (isSummaryRow && accountSummaryRows.length) {
                                if (col === 4) {
                                    const dRefs = accountSummaryRows.map(r => `D${r}`).join(
                                        '+');
                                    val = dRefs ? `=${dRefs}` : val;
                                }
                                if (col === 5) {
                                    const eRefs = accountSummaryRows.map(r => `E${r}`).join(
                                        '+');
                                    val = eRefs ? `=${eRefs}` : val;
                                }
                            }
                        }
                        out[ci] = val;

                        // HTML cell build
                        const tag = isHead ? 'th' : 'td';
                        let cellStyle = '';
                        if (isHead) cellStyle += 'background-color:#dadada;';
                        if (tdBg) cellStyle += `background-color:${tdBg};`;
                        if ((isAccountSummary || isSummaryRow) && ci === 1) cellStyle +=
                            'font-style:italic;';

                        const col = ci + 1;
                        const isFormulaCell = (!isHead && (
                            col === 6 || col === 7 ||
                            (isAccountSummary && (col === 4 || col === 5)) ||
                            (isSummaryRow && (col === 4 || col === 5))
                        ));
                        const htmlVal = isHead ? raw : (isFormulaCell ? val : raw);
                        const escaped = escapeHtml(htmlVal);
                        const labelBoldItalic = (!isHead && (isAccountSummary ||
                            isSummaryRow) && ci === 1);
                        const content = labelBoldItalic ? `<b><i>${escaped}</i></b>` : escaped;
                        htmlCells.push(
                            `<${tag}${span > 1 ? ` colspan="${span}"` : ''}${cellStyle ? ` style="${cellStyle}"` : ''}>${content}</${tag}>`
                        );
                    }
                    ci++;
                }
            });

            htmlRow += htmlCells.join('') + '</tr>';
            if (isHead) headHtmlRows.push(htmlRow);
            else if (isFoot) footHtmlRows.push(htmlRow);
            else bodyHtmlRows.push(htmlRow);

            if (isAccountSummary) {
                groupStartRow = null;
                groupEndRow = null;
            }

            return out.join('\t');
        }));

        const tsv = lines.join('\n');
        const allBodyRows = bodyHtmlRows.concat(footHtmlRows);
        const html =
            `<table>${headHtmlRows.length ? `<thead>${headHtmlRows.join('')}</thead>` : ''}${allBodyRows.length ? `<tbody>${allBodyRows.join('')}</tbody>` : ''}</table>`;

        const fallbackCopyText = (text) => {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.top = '-1000px';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
        };

        const fallbackCopyMixed = () => {
            const onCopy = (e) => {
                try {
                    e.clipboardData.setData('text/html', html);
                    e.clipboardData.setData('text/plain', tsv);
                    e.preventDefault();
                } catch (_) {}
            };
            document.addEventListener('copy', onCopy, { once: true });
            document.execCommand('copy');
        };

        (async () => {
            try {
                if (navigator.clipboard && window.ClipboardItem) {
                    const item = new ClipboardItem({
                        'text/plain': new Blob([tsv], { type: 'text/plain' }),
                        'text/html': new Blob([html], { type: 'text/html' })
                    });
                    await navigator.clipboard.write([item]);
                } else if (navigator.clipboard && window.isSecureContext && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(tsv);
                } else {
                    try { fallbackCopyMixed(); } catch (_) { fallbackCopyText(tsv); }
                }
            } catch (e) {
                try { fallbackCopyMixed(); } catch (_) { fallbackCopyText(tsv); }
            }
        })();
    };

    // Build HTML table string (used for email body). This version outputs FINAL values (no formulas)
    window.RB_extractTableHtml = window.RB_extractTableHtml || function(table, dateStr) {
        if (!table) return '';
        const headRow = table.querySelector('thead tr');
        const colCount = headRow ? Array.from(headRow.cells).reduce((a, c) => a + (c.colSpan || 1), 0) : 10;
        const rows = Array.from(table.querySelectorAll('thead tr, tbody tr, tfoot tr'));
        const moneyToNum = s => (s || '').replace(/[^0-9.\-]/g, '');
        const escapeHtml = (s) => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        const fmtMoney = (n) => (isFinite(n) ? ('$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })) : '');
        const fmtPercent = (n) => (isFinite(n) ? ((n * 100).toFixed(2) + '%') : '');

        const headHtmlRows = [];
        const bodyHtmlRows = [];
        const footHtmlRows = [];

        // Add date row at the top
        const dateHtmlRow = `<tr><td colspan="${colCount}" style="text-align:left;font-weight:bold;border:1px solid #bbb;padding:6px;">${escapeHtml(dateStr || '')}</td></tr>`;
        headHtmlRows.push(dateHtmlRow);

        // Track per-account ranges and account summary rows
        let groupStartRow = null;
        let groupEndRow = null;

        rows.forEach((tr, idx) => {
            const rowNumber = idx + 2; // date row is 1, header is 2
            const isHead = tr.parentElement && tr.parentElement.tagName.toLowerCase() === 'thead';
            const cells = Array.from(tr.cells);
            const parentTag = tr.parentElement ? tr.parentElement.tagName.toLowerCase() : '';
            const secondText = (cells[1] ? (cells[1].innerText || '') : '').replace(/\s+/g, ' ').trim();
            const isAccountSummary = !isHead && parentTag === 'tbody' && secondText.toLowerCase() === 'account summary';
            const isSpacer = !isHead && parentTag === 'tbody' && cells.length === 1 && ((cells[0].colSpan || 1) >= (headRow ? headRow.cells.length : 10));
            const isFoot = parentTag === 'tfoot';
            const isSummaryRow = isFoot && secondText.toLowerCase() === 'summary';
            const isDataRow = !isHead && !isAccountSummary && !isSpacer && !isFoot;

            if (isDataRow) {
                if (groupStartRow === null) groupStartRow = rowNumber;
                groupEndRow = rowNumber;
            }

            const tag = isHead ? 'th' : 'td';
            let htmlRow = `<tr${(isAccountSummary || isSummaryRow) ? ' style="font-weight:600;"' : ''}>`;
            const htmlCells = [];
            let ci = 0;

            // Pre-calc row numeric values for Spend (D = col 4) and Revenue (E = col 5)
            const rawTexts = cells.map(td => (td.innerText || '').replace(/\s+/g, ' ').trim());
            const spendNum = parseFloat(moneyToNum(rawTexts[3] || ''));
            const revenueNum = parseFloat(moneyToNum(rawTexts[4] || ''));
            const plNum = (isFinite(spendNum) && isFinite(revenueNum)) ? (revenueNum - spendNum) : NaN;
            const roiNum = (isFinite(spendNum) && spendNum > 0 && isFinite(revenueNum)) ? ((revenueNum / spendNum) - 1) : NaN;

            cells.forEach(td => {
                const span = td.colSpan || 1;
                const raw = (td.innerText || '').replace(/\s+/g, ' ').trim();
                const tdBg = (td && td.style && td.style.backgroundColor) ? td.style.backgroundColor : '';
                for (let i = 0; i < span; i++) {
                    if (ci >= colCount) break;
                    if (i === 0) {
                        const col = ci + 1;
                        let val = raw;
                        // Compute final values for P/L (F col=6) and ROI (G col=7)
                        if (!isHead) {
                            if (col === 6) val = isFinite(plNum) ? fmtMoney(plNum) : '';
                            if (col === 7) val = isFinite(roiNum) ? fmtPercent(roiNum) : '';
                        }
                        let cellStyle = 'border:1px solid #bbb;padding:6px;';
                        if (isHead) cellStyle += 'background-color:#efefef;';
                        if (tdBg) cellStyle += `background-color:${tdBg};`;
                        if ((isAccountSummary || isSummaryRow) && ci === 1) cellStyle += 'font-style:italic;';

                        // Conditional color for P/L and ROI
                        if (!isHead && (col === 6 || col === 7)) {
                            const positive = (col === 6) ? (plNum > 0) : (roiNum > 0);
                            const negative = (col === 6) ? (plNum < 0) : (roiNum < 0);
                            if (positive) cellStyle += 'background-color:#d4edda;';
                            if (negative) cellStyle += 'background-color:#f8d7da;';
                        }

                        const htmlVal = (!isHead && (col === 6 || col === 7) && (val !== '' && val != null)) ? val : raw;
                        const escaped = escapeHtml(htmlVal);
                        const labelBoldItalic = (!isHead && (isAccountSummary || isSummaryRow) && ci === 1);
                        const content = labelBoldItalic ? `<b><i>${escaped}</i></b>` : escaped;
                        htmlCells.push(`<${tag}${span > 1 ? ` colspan="${span}"` : ''}${cellStyle ? ` style="${cellStyle}"` : ''}>${content}</${tag}>`);
                    }
                    ci++;
                }
            });
            htmlRow += htmlCells.join('') + '</tr>';
            if (isHead) headHtmlRows.push(htmlRow);
            else if (isFoot) footHtmlRows.push(htmlRow);
            else bodyHtmlRows.push(htmlRow);

            if (isAccountSummary) {
                groupStartRow = null;
                groupEndRow = null;
            }
        });

        const allBodyRows = bodyHtmlRows.concat(footHtmlRows);
        const html = `<table style="border-collapse:collapse;width:100%;">${headHtmlRows.length ? `<thead>${headHtmlRows.join('')}</thead>` : ''}${allBodyRows.length ? `<tbody>${allBodyRows.join('')}</tbody>` : ''}</table>`;
        return html;
    };

    // Build values for Google Sheet (same as RB_createSheet)
    window.RB_buildSheetValues = window.RB_buildSheetValues || function(table, dateStr) {
        if (!table) return [];
        const headRow = table.querySelector('thead tr');
        const colCount = headRow ? Array.from(headRow.cells).reduce((a, c) => a + (c.colSpan || 1), 0) : 10;
        const rows = Array.from(table.querySelectorAll('thead tr, tbody tr, tfoot tr'));
        const moneyToNum = s => (s || '').replace(/[^0-9.\-]/g, '');
        const values = [];
        const dateRow = new Array(colCount).fill('');
        dateRow[0] = dateStr || '';
        values.push(dateRow);
        let groupStartRow = null;
        let groupEndRow = null;
        const accountSummaryRows = [];
        const isHeadTag = (tr) => tr.parentElement && tr.parentElement.tagName.toLowerCase() === 'thead';
        rows.forEach((tr, idx) => {
            const rowNumber = idx + 2; // date row is 1
            const isHead = isHeadTag(tr);
            const cells = Array.from(tr.cells);
            const parentTag = tr.parentElement ? tr.parentElement.tagName.toLowerCase() : '';
            const secondText = (cells[1] ? (cells[1].innerText || '') : '').replace(/\s+/g, ' ').trim();
            const isAccountSummary = !isHead && parentTag === 'tbody' && secondText.toLowerCase() === 'account summary';
            const isSpacer = !isHead && parentTag === 'tbody' && cells.length === 1 && ((cells[0].colSpan || 1) >= (headRow ? headRow.cells.length : 10));
            const isFoot = parentTag === 'tfoot';
            const isSummaryRow = isFoot && secondText.toLowerCase() === 'summary';
            const isDataRow = !isHead && !isAccountSummary && !isSpacer && !isFoot;
            if (isDataRow) {
                if (groupStartRow === null) groupStartRow = rowNumber;
                groupEndRow = rowNumber;
            }
            if (isAccountSummary) accountSummaryRows.push(rowNumber);
            const out = new Array(colCount).fill('');
            cells.forEach((td, ci0) => {
                const span = td.colSpan || 1;
                const raw = (td.innerText || '').replace(/\s+/g, ' ').trim();
                let ci = ci0;
                if (span > 1) ci = ci0; // only write into the first spanned column
                if (ci < colCount) {
                    let val = raw;
                    if (!isHead) {
                        const col = ci + 1;
                        if (col === 3 || col === 4 || col === 5 || col === 9 || col === 10) val = moneyToNum(val) || '';
                        if (col === 6) val = raw ? `=E${rowNumber}-D${rowNumber}` : '';
                        if (col === 7) val = raw ? `=IF(D${rowNumber}>0, (E${rowNumber}/D${rowNumber})-1, "")` : '';
                        if (isAccountSummary && groupStartRow !== null && groupEndRow !== null) {
                            if (col === 4) val = `=SUM(D${groupStartRow}:D${groupEndRow})`;
                            if (col === 5) val = `=SUM(E${groupStartRow}:E${groupEndRow})`;
                        }
                        if (isSummaryRow && accountSummaryRows.length) {
                            if (col === 4) val = `=${accountSummaryRows.map(r => `D${r}`).join('+')}`;
                            if (col === 5) val = `=${accountSummaryRows.map(r => `E${r}`).join('+')}`;
                        }
                    }
                    out[ci] = val;
                }
            });
            values.push(out);
            if (isAccountSummary) { groupStartRow = null; groupEndRow = null; }
        });
        return values;
    };

    // Silently create a Google Sheet and return its URL (handles OAuth)
    window.RB_createSheetSilently = window.RB_createSheetSilently || async function(table, dateStr, cadence, dateTo) {
        try {
            const values = RB_buildSheetValues(table, dateStr);
            const payload = { title: `${dateStr || ''} - Rumble Ads`, values, cadence, date_to: dateTo };
            const postOnce = async () => fetch('{{ route('google.sheets.rumble.create') }}', {
                method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify(payload)
            });
            let resp = await postOnce();
            if (resp.status === 401) {
                const data = await resp.json().catch(() => ({}));
                if (data && data.authorizeUrl) {
                    const authUrl = data.authorizeUrl;
                    const authWin = window.open(authUrl, '_blank');
                    await new Promise((resolve, reject) => {
                        const onMsg = async (ev) => {
                            try {
                                if (ev.origin !== window.location.origin) return;
                                if (!ev.data || ev.data.type !== 'google-auth-success') return;
                                window.removeEventListener('message', onMsg);
                                resolve();
                            } catch (e) { reject(e); }
                        };
                        window.addEventListener('message', onMsg);
                        setTimeout(() => { try { window.removeEventListener('message', onMsg); } catch(_){}; reject(new Error('auth-timeout')); }, 120000);
                    }).catch(() => { /* ignore */ });
                    try { if (authWin && !authWin.closed) authWin.close(); } catch(_){}
                    resp = await postOnce();
                }
            }
            if (!resp.ok) return null;
            const json = await resp.json().catch(() => ({}));
            return json && json.spreadsheetUrl ? json.spreadsheetUrl : null;
        } catch (_) { return null; }
    };

    // Create Gmail Draft: build table HTML, ensure Sheet exists, then call Gmail draft endpoint (handles OAuth)
    window.RB_createDraft = window.RB_createDraft || async function(table, dateStr, cadence, dateTo, dateFrom) {
        if (!table) return;
        try {
            // 1) Build HTML table for email body (with greeting and footer)
            const tableHtml = RB_extractTableHtml(table, dateStr);

            // 2) Ensure a Google Sheet is created to link in the email
            const sheetUrl = await RB_createSheetSilently(table, dateStr, cadence, dateTo);

            // Compose final HTML like the approved layout
            // Build dynamic date text: for daily, use 'yesterday' only if dateTo is exactly yesterday; else show d/m/Y.
            // For weekly/monthly, show a range d/m/Y - d/m/Y when dateFrom is provided; otherwise use dateStr or dateTo (d/m/Y).
            const parseLocalYMD = (s) => {
                if (typeof s !== 'string') return null;
                const m = s.match(/^(\d{4})-(\d{2})-(\d{2})$/);
                if (!m) return null;
                return new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
            };
            const toLocalDate = (v) => {
                if (!v) return null;
                if (v instanceof Date) return new Date(v.getFullYear(), v.getMonth(), v.getDate());
                const d = parseLocalYMD(v);
                return d ? new Date(d.getFullYear(), d.getMonth(), d.getDate()) : null;
            };
            const fmtDMY = (d) => {
                if (!(d instanceof Date)) return '';
                const dd = String(d.getDate()).padStart(2, '0');
                const mm = String(d.getMonth() + 1).padStart(2, '0');
                const yyyy = d.getFullYear();
                return `${dd}/${mm}/${yyyy}`;
            };
            const dtTo = toLocalDate(dateTo);
            const today = new Date();
            const yest = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 1);
            let linkText = '';
            if (cadence === 'daily') {
                const isYest = dtTo && dtTo.getFullYear() === yest.getFullYear() && dtTo.getMonth() === yest.getMonth() && dtTo.getDate() === yest.getDate();
                linkText = isYest ? 'yesterday' : (dtTo ? fmtDMY(dtTo) : (dateStr || 'the selected period'));
            } else {
                const dtFrom = toLocalDate(dateFrom);
                if (dtFrom && dtTo) linkText = `${fmtDMY(dtFrom)} - ${fmtDMY(dtTo)}`;
                else if (dateStr) linkText = dateStr;
                else linkText = dtTo ? fmtDMY(dtTo) : 'the selected period';
            }
            const linkedPhrase = sheetUrl ? `<a href="${sheetUrl}" target="_blank" rel="noopener">${linkText}</a>` : linkText;
            const cadenceLabel = (cadence === 'daily') ? 'Daily' : (cadence ? cadence.charAt(0).toUpperCase() + cadence.slice(1) : 'Daily');
            const bodyTop = `<p style="margin:0 0 12px 0;">Hello Jesse,</p>` +
                            `<p style="margin:0 0 16px 0;">Here is the Rumble ${cadenceLabel} Report from ${linkedPhrase}.</p>`;
            const bodyBottom = `<p style=\"margin:16px 0 0 0;\">Thanks,<br/>Allan</p>`;
            const html = `<div style=\"font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.4;\">${bodyTop}${tableHtml}${bodyBottom}</div>`;

            // 3) Call Gmail draft endpoint
            const payload = {
                html,
                cadence,
                date_to: dateTo,
                date_from: dateFrom || null,
                date_str: dateStr || '',
                sheet_url: sheetUrl || '',
                is_full_body: true
            };

            const postOnce = async () => fetch('{{ route('google.gmail.rumble.create_draft') }}', {
                method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify(payload)
            });

            let resp = await postOnce();
            if (resp.status === 401) {
                const data = await resp.json().catch(() => ({}));
                if (data && data.authorizeUrl) {
                    const authUrl = data.authorizeUrl;
                    const authWin = window.open(authUrl, '_blank');
                    await new Promise((resolve, reject) => {
                        const onMsg = async (ev) => {
                            try {
                                if (ev.origin !== window.location.origin) return;
                                if (!ev.data || ev.data.type !== 'google-auth-success') return;
                                window.removeEventListener('message', onMsg);
                                resolve();
                            } catch (e) { reject(e); }
                        };
                        window.addEventListener('message', onMsg);
                        setTimeout(() => { try { window.removeEventListener('message', onMsg); } catch(_){}; reject(new Error('auth-timeout')); }, 120000);
                    }).catch(() => { /* ignore */ });
                    try { if (authWin && !authWin.closed) authWin.close(); } catch(_){}
                    resp = await postOnce();
                }
            }

            if (!resp.ok) {
                const errData = await resp.json().catch(() => null);
                const msg = errData && (errData.message || errData.error) ? (errData.message || errData.error) : `HTTP ${resp.status}`;
                alert('Failed to create Gmail draft: ' + msg);
                return;
            }
            const data = await resp.json().catch(() => ({}));
            if (data && data.draftId) {
                alert('Gmail draft created successfully. Draft ID: ' + data.draftId + '\nCheck your Gmail Drafts folder.');
            } else {
                alert('Gmail draft created.');
            }
        } catch (e) {
            console.error(e);
            alert('Failed to create Gmail draft.');
        }
    };

    window.RB_createSheet = window.RB_createSheet || async function(table, dateStr, cadence, dateTo) {
        if (!table) return;

        // Open a tab immediately to avoid popup blockers, we'll navigate it later
        let pendingWin = null;
        try { pendingWin = window.open('', '_blank'); } catch (_) {}

        const headRow = table.querySelector('thead tr');
        const colCount = headRow ? Array.from(headRow.cells).reduce((a, c) => a + (c.colSpan || 1), 0) : 10;
        const rows = Array.from(table.querySelectorAll('thead tr, tbody tr, tfoot tr'));
        const moneyToNum = s => (s || '').replace(/[^0-9.\-]/g, '');

        const values = [];
        // top date row
        const dateRow = new Array(colCount).fill('');
        dateRow[0] = dateStr || '';
        values.push(dateRow);

        // Track per-account ranges and account summary rows
        let groupStartRow = null;
        let groupEndRow = null;
        const accountSummaryRows = [];

        const isHeadTag = (tr) => tr.parentElement && tr.parentElement.tagName.toLowerCase() === 'thead';

        rows.forEach((tr, idx) => {
            const rowNumber = idx + 2; // date row is 1
            const isHead = isHeadTag(tr);
            const cells = Array.from(tr.cells);
            const parentTag = tr.parentElement ? tr.parentElement.tagName.toLowerCase() : '';
            const secondText = (cells[1] ? (cells[1].innerText || '') : '').replace(/\s+/g, ' ').trim();
            const isAccountSummary = !isHead && parentTag === 'tbody' && secondText.toLowerCase() === 'account summary';
            const isSpacer = !isHead && parentTag === 'tbody' && cells.length === 1 && ((cells[0].colSpan || 1) >= (headRow ? headRow.cells.length : 10));
            const isFoot = parentTag === 'tfoot';
            const isSummaryRow = isFoot && secondText.toLowerCase() === 'summary';
            const isDataRow = !isHead && !isAccountSummary && !isSpacer && !isFoot;

            if (isDataRow) {
                if (groupStartRow === null) groupStartRow = rowNumber;
                groupEndRow = rowNumber;
            }
            if (isAccountSummary) accountSummaryRows.push(rowNumber);

            const out = new Array(colCount).fill('');
            cells.forEach((td, ci0) => {
                const span = td.colSpan || 1;
                const raw = (td.innerText || '').replace(/\s+/g, ' ').trim();
                let ci = ci0;
                if (span > 1) ci = ci0; // only write into the first spanned column
                if (ci < colCount) {
                    let val = raw;
                    if (!isHead) {
                        const col = ci + 1;
                        if (col === 3 || col === 4 || col === 5 || col === 9 || col === 10) {
                            val = moneyToNum(val) || '';
                        }
                        if (col === 6) {
                            val = raw ? `=E${rowNumber}-D${rowNumber}` : '';
                        }
                        if (col === 7) {
                            // Keep numeric ROI so number format + conditional formatting can apply
                            val = raw ? `=IF(D${rowNumber}>0, (E${rowNumber}/D${rowNumber})-1, "")` : '';
                        }
                        if (isAccountSummary && groupStartRow !== null && groupEndRow !== null) {
                            if (col === 4) val = `=SUM(D${groupStartRow}:D${groupEndRow})`;
                            if (col === 5) val = `=SUM(E${groupStartRow}:E${groupEndRow})`;
                        }
                        if (isSummaryRow && accountSummaryRows.length) {
                            if (col === 4) val = `=${accountSummaryRows.map(r => `D${r}`).join('+')}`;
                            if (col === 5) val = `=${accountSummaryRows.map(r => `E${r}`).join('+')}`;
                        }
                    }
                    out[ci] = val;
                }
            });

            // push header/body/footer rows
            values.push(out);

            if (isAccountSummary) {
                groupStartRow = null;
                groupEndRow = null;
            }
        });

        try {
            const payload = {
                title: `${dateStr || ''} - Rumble Ads`,
                values,
                cadence,
                date_to: dateTo,
            };

            const resp = await fetch('{{ route('google.sheets.rumble.create') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            });
            if (resp.status === 401) {
                const data = await resp.json().catch(() => ({}));
                if (data && data.authorizeUrl) {
                    // Attach listener BEFORE navigating, to avoid race where popup posts before listener is ready
                    const onMsg = async (ev) => {
                        try {
                            if (!ev) return;
                            // Only accept messages from our own origin
                            if (ev.origin !== window.location.origin) return;
                            if (!ev.data || ev.data.type !== 'google-auth-success') return;
                            window.removeEventListener('message', onMsg);
                            const retry = await fetch('{{ route('google.sheets.rumble.create') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify(payload)
                            });
                            if (!retry.ok) {
                                const errData = await retry.json().catch(() => null);
                                const msg = errData && (errData.message || errData.error) ? (errData.message || errData.error) : `HTTP ${retry.status}`;
                                alert('Failed to create Google Sheet after authorization: ' + msg);
                                try { if (pendingWin && !pendingWin.closed) pendingWin.close(); } catch (_) {}
                                return;
                            }
                            const done = await retry.json();
                            if (done && done.spreadsheetUrl) {
                                if (pendingWin && !pendingWin.closed) pendingWin.location.href = done.spreadsheetUrl; else {
                                    try { pendingWin = window.open(done.spreadsheetUrl, '_blank'); } catch (_) {}
                                }
                            }
                        } catch (e) {
                            console.error(e);
                            alert('Failed to create Google Sheet after authorization.');
                            try { if (pendingWin && !pendingWin.closed) pendingWin.close(); } catch (_) {}
                        }
                    };
                    window.addEventListener('message', onMsg);

                    // Navigate popup after listener is ready
                    const authUrl = data.authorizeUrl;
                    if (pendingWin && !pendingWin.closed) pendingWin.location.href = authUrl; else {
                        try { pendingWin = window.open(authUrl, '_blank'); } catch (_) {}
                    }

                    // Fallback timeout: if message never arrives, clean up and notify
                    setTimeout(() => {
                        try { window.removeEventListener('message', onMsg); } catch (_) {}
                        try { if (pendingWin && !pendingWin.closed) pendingWin.close(); } catch (_) {}
                        alert('Authorization timed out. Please try again.');
                    }, 120000);
                    return;
                }
            }
            if (!resp.ok) {
                const errData = await resp.json().catch(() => null);
                const msg = errData && (errData.message || errData.error) ? (errData.message || errData.error) : `HTTP ${resp.status}`;
                alert('Failed to create Google Sheet: ' + msg);
                try { if (pendingWin && !pendingWin.closed) pendingWin.close(); } catch (_) {}
                return;
            }
            const data = await resp.json();
            if (data && data.spreadsheetUrl) {
                if (pendingWin && !pendingWin.closed) pendingWin.location.href = data.spreadsheetUrl; else window.open(data.spreadsheetUrl, '_blank');
            }
        } catch (e) {
            console.error(e);
            alert('Failed to create Google Sheet.');
            try { if (pendingWin && !pendingWin.closed) pendingWin.close(); } catch (_) {}
        }
    };
</script>
