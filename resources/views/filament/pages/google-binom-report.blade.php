@php
    $sections = $this->buildGroupedByUploadDate();
    $filters = $this->filters;
    $rt = $filters['report_type'] ?? 'weekly';
@endphp

<x-filament-panels::page>
    <div class="space-y-3">
        <div class="flex items-center justify-end">
            <div class="inline-flex divide-x divide-gray-200 dark:divide-gray-700 rounded-md shadow-sm overflow-hidden ring-1 ring-gray-200 dark:ring-gray-700 bg-white dark:bg-gray-900" role="group">
                <button type="button" wire:click="setReportType('weekly')"
                    class="px-3 py-1.5 text-xs font-medium focus:outline-none transition
                    {{ $rt === 'weekly' ? 'bg-amber-500 text-white' : 'bg-gray-50 text-gray-800 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }} rounded-l-md">
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
            <div x-data="{ open: false, copying: false, copyingSummary: false, creating: false, creatingDraft: false }" class="rounded-lg border bg-white" wire:key="section-{{ $section['report_type'] }}-{{ $section['date_from'] }}-{{ $section['date_to'] }}">
                <div @click="open = !open" class="flex items-center justify-between px-4 py-3 cursor-pointer">
                    <div class="font-medium text-gray-700">
                        {{ \Illuminate\Support\Carbon::parse($section['date_from'])->format('F j, Y') }} — {{ \Illuminate\Support\Carbon::parse($section['date_to'])->format('F j, Y') }}
                        <span class="ml-2 text-[10px] rounded bg-gray-100 px-1.5 py-0.5 text-gray-600">{{ strtoupper($section['report_type'] ?? ($report['report_type'] ?? '')) }}</span>
                        <span class="text-xs text-gray-500">({{ $section['count'] }} entries)</span>
                    </div>
                    <div class="text-sm text-gray-600 flex items-center gap-2">
                        <span class="font-semibold">{{ $this->fmtMoney($report['totals']['spend'] ?? 0) }} spent</span>
                        <span class="text-gray-400">·</span>
                        <span class="font-semibold">{{ $this->fmtMoney($report['totals']['revenue'] ?? 0) }} revenue</span>
                        <button type="button" @click.stop="GB_copyTable($refs.tbl, '{{ \Illuminate\Support\Carbon::parse($section['date_from'])->format('d/m') }} - {{ \Illuminate\Support\Carbon::parse($section['date_to'])->format('d/m') }}'); copying = true; setTimeout(()=>copying=false, 1200)" title="Copy this table to clipboard"
                            class="ml-2 text-red-600 hover:text-red-700 font-semibold text-xs uppercase tracking-wide {{ $report['has_rows'] ? '' : 'opacity-40 pointer-events-none' }}">
                            <span x-show="!copying">COPY TABLE</span>
                            <span x-show="copying" class="text-green-600">COPIED</span>
                        </button>
                        <button type="button" @click.stop="GB_copySummary($refs.tbl); copyingSummary = true; setTimeout(()=>copyingSummary=false, 1200)" title="Copy account summaries to clipboard"
                            class="ml-2 text-indigo-600 hover:text-indigo-700 font-semibold text-xs uppercase tracking-wide {{ $report['has_rows'] ? '' : 'opacity-40 pointer-events-none' }}">
                            <span x-show="!copyingSummary">COPY SUMMARY</span>
                            <span x-show="copyingSummary" class="text-green-600">COPIED</span>
                        </button>
                        <button type="button"
                            @click.stop="creating=true; GB_createSheet($refs.tbl,
                              '{{ \Illuminate\Support\Carbon::parse($section['date_from'])->format('d/m') }} - {{ \Illuminate\Support\Carbon::parse($section['date_to'])->format('d/m') }}',
                              '{{ $section['report_type'] ?? 'weekly' }}',
                              '{{ $section['date_to'] }}'
                            ).finally(()=>creating=false)"
                            title="Create a Google Sheet with this table"
                            class="ml-2 text-blue-600 hover:text-blue-700 font-semibold text-xs uppercase tracking-wide {{ $report['has_rows'] ? '' : 'opacity-40 pointer-events-none' }}">
                            <span x-show="!creating">CREATE SHEET</span>
                            <span x-show="creating" class="text-green-600">CREATING…</span>
                        </button>
                        <button type="button"
                            @click.stop="creatingDraft=true; GB_createDraft($refs.tbl,
                              '{{ \Illuminate\Support\Carbon::parse($section['date_from'])->format('d/m') }} - {{ \Illuminate\Support\Carbon::parse($section['date_to'])->format('d/m') }}',
                              '{{ $section['report_type'] ?? 'weekly' }}',
                              '{{ $section['date_to'] }}',
                              '{{ $section['date_from'] }}'
                            ).finally(()=>creatingDraft=false)"
                            title="Create a Gmail draft with SUMMARY table"
                            class="ml-2 text-emerald-600 hover:text-emerald-700 font-semibold text-xs uppercase tracking-wide {{ $report['has_rows'] ? '' : 'opacity-40 pointer-events-none' }}">
                            <span x-show="!creatingDraft">CREATE DRAFT</span>
                            <span x-show="creatingDraft" class="text-green-600">CREATING…</span>
                        </button>
                        <span class="ml-3 text-[11px] text-gray-500 uppercase tracking-wide" title="Full mode includes all previous-period campaigns for the account/overall.">ROI Last</span>
                        <div class="inline-flex divide-x divide-gray-200 dark:divide-gray-700 rounded-md shadow-sm overflow-hidden ring-1 ring-gray-200 dark:ring-gray-700 bg-white dark:bg-gray-900" role="group" title="Cohort mode only includes the campaigns present in the current table (current-period cohort).">
                            <button type="button" wire:click="setRoiPrevMode('full')" title="Full mode includes all previous-period campaigns for the account/overall."
                                class="px-2.5 py-1 text-[11px] font-medium focus:outline-none transition {{ $this->roiPrevMode === 'full' ? 'bg-amber-500 text-white' : 'bg-gray-50 text-gray-800 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }} rounded-l-md">
                                Full
                            </button>
                            <button type="button" wire:click="setRoiPrevMode('cohort')" title="Cohort mode only includes the campaigns present in the current table (current-period cohort)."
                                class="px-2.5 py-1 text-[11px] font-medium focus:outline-none transition {{ $this->roiPrevMode === 'cohort' ? 'bg-amber-500 text-white' : 'bg-gray-50 text-gray-800 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }} rounded-r-md">
                                Cohort
                            </button>
                        </div>
                        <svg x-bind:class="open ? 'rotate-180' : ''" class="h-4 w-4 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                </div>
                <div x-show="open" x-collapse class="overflow-x-auto">
                    @if($report['has_rows'])
                        <table x-ref="tbl" class="min-w-full text-xs">
                            <thead class="bg-gray-50">
                                <tr class="text-left">
                                    <th class="px-3 py-2 font-medium text-gray-600">ACCOUNT NAME</th>
                                    <th class="px-3 py-2 font-medium text-gray-600">CAMPAIGN NAME</th>
                                    <th class="px-3 py-2 font-medium text-gray-600">TOTAL SPEND</th>
                                    <th class="px-3 py-2 font-medium text-gray-600">REVENUE</th>
                                    <th class="px-3 py-2 font-medium text-gray-600">P/L</th>
                                    <th class="px-3 py-2 font-medium text-gray-600">ROI</th>
                                    <th class="px-3 py-2 font-medium text-gray-600">
                                        {{ $rt === 'weekly' ? 'ROI LAST WEEK' : 'ROI LAST MONTH' }}
                                        <span class="ml-1 text-[10px] text-gray-500">({{ $this->roiPrevMode === 'cohort' ? 'Cohort' : 'Full' }})</span>
                                    </th>
                                    <th class="px-3 py-2 font-medium text-gray-600">SALES</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($report['groups'] as $group)
                                    @foreach($group['rows'] as $row)
                                        <tr class="border-t">
                                            <td class="px-3 py-2 text-gray-600">{{ $row['account'] }}</td>
                                            <td class="px-3 py-2 text-gray-600">{{ $row['campaign_name'] }}</td>
                                            <td class="px-3 py-2 text-gray-600">{{ $this->fmtMoney($row['spend']) }}</td>
                                            <td class="px-3 py-2 text-gray-600">{{ $this->fmtMoney($row['revenue']) }}</td>
                                            <td class="px-3 py-2 text-gray-600" style="{{ (($row['pl'] ?? 0) > 0) ? 'background-color:#a3da9d' : ((($row['pl'] ?? 0) < 0) ? 'background-color:#ff8080' : '') }}">{{ $this->fmtMoney($row['pl']) }}</td>
                                            <td class="px-3 py-2 text-gray-600" style="{{ (($row['roi'] ?? 0) > 0) ? 'background-color:#a3da9d' : ((($row['roi'] ?? 0) < 0) ? 'background-color:#ff8080' : '') }}">{{ $this->fmtPercent($row['roi']) }}</td>
                                            <td class="px-3 py-2 text-gray-600" style="{{ (($row['roi_prev'] ?? 0) > 0) ? 'background-color:#a3da9d' : ((($row['roi_prev'] ?? 0) < 0) ? 'background-color:#ff8080' : '') }}">{{ $this->fmtPercent($row['roi_prev']) }}</td>
                                            <td class="px-3 py-2 text-gray-600">{{ $row['sales'] ?? '' }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="border-t bg-gray-50 font-semibold">
                                        <td class="px-3 py-2 text-gray-700">{{ $group['account'] }}</td>
                                        <td class="px-3 py-2 italic text-gray-700">Account Summary</td>
                                        <td class="px-3 py-2 text-gray-700">{{ $this->fmtMoney($group['summary']['spend'] ?? 0) }}</td>
                                        <td class="px-3 py-2 text-gray-700">{{ $this->fmtMoney($group['summary']['revenue'] ?? 0) }}</td>
                                        <td class="px-3 py-2 text-gray-700" style="{{ (($group['summary']['pl'] ?? 0) > 0) ? 'background-color:#a3da9d' : ((($group['summary']['pl'] ?? 0) < 0) ? 'background-color:#ff8080' : '') }}">{{ $this->fmtMoney(($group['summary']['pl'] ?? 0)) }}</td>
                                        @php($gRoi = $group['summary']['roi'] ?? null)
                                        <td class="px-3 py-2 text-gray-700" style="{{ (($gRoi ?? 0) > 0) ? 'background-color:#a3da9d' : ((($gRoi ?? 0) < 0) ? 'background-color:#ff8080' : '') }}">{{ $this->fmtPercent($group['summary']['roi'] ?? null) }}</td>
                                        @php($gRoiPrev = $group['summary']['roi_prev'] ?? null)
                                        <td class="px-3 py-2 text-gray-700" data-roi-prev="{{ $this->fmtPercent($group['summary']['roi_prev'] ?? null) }}" style="{{ (($gRoiPrev ?? 0) > 0) ? 'background-color:#a3da9d' : ((($gRoiPrev ?? 0) < 0) ? 'background-color:#ff8080' : '') }}">{{ $this->fmtPercent($group['summary']['roi_prev'] ?? null) }}</td>
                                        <td class="px-3 py-2"></td>
                                    </tr>
                                    <tr class="border-0">
                                        <td colspan="8" class="px-3 py-2"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr class="border-t font-semibold">
                                    <td class="px-3 py-2 text-gray-700"></td>
                                    <td class="px-3 py-2 italic text-gray-700">SUMMARY</td>
                                    <td class="px-3 py-2 text-gray-700">{{ $this->fmtMoney($report['totals']['spend'] ?? 0) }}</td>
                                    <td class="px-3 py-2 text-gray-700">{{ $this->fmtMoney($report['totals']['revenue'] ?? 0) }}</td>
                                    <td class="px-3 py-2 text-gray-700" style="{{ (($report['totals']['pl'] ?? 0) > 0) ? 'background-color:#a3da9d' : ((($report['totals']['pl'] ?? 0) < 0) ? 'background-color:#ff8080' : '') }}">{{ $this->fmtMoney($report['totals']['pl'] ?? 0) }}</td>
                                    @php($tRoi = $report['totals']['roi'] ?? null)
                                    <td class="px-3 py-2 text-gray-700" style="{{ (($tRoi ?? 0) > 0) ? 'background-color:#a3da9d' : ((($tRoi ?? 0) < 0) ? 'background-color:#ff8080' : '') }}">{{ $this->fmtPercent($report['totals']['roi'] ?? null) }}</td>
                                    @php($tRoiPrev = $report['totals']['roi_prev'] ?? null)
                                    <td class="px-3 py-2 text-gray-700" data-roi-prev="{{ $this->fmtPercent($report['totals']['roi_prev'] ?? null) }}" style="{{ (($tRoiPrev ?? 0) > 0) ? 'background-color:#a3da9d' : ((($tRoiPrev ?? 0) < 0) ? 'background-color:#ff8080' : '') }}">{{ $this->fmtPercent($report['totals']['roi_prev'] ?? null) }}</td>
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
            <div class="rounded-lg border bg-white p-4 text-sm text-gray-600">No uploads found for the selected report type.</div>
        @endforelse
    </div>

    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <h2 class="text-lg font-semibold">Google Binom Report</h2>
                <p class="text-xs text-gray-600">Type: <span class="font-medium">{{ strtoupper($filters['report_type'] ?? 'weekly') }}</span></p>
            </div>
             <div class="flex items-center gap-2">
                <span class="text-[11px] text-gray-600" title="Full mode includes all previous-period campaigns for the account/overall.">ROI Last mode:</span>
                <div class="inline-flex divide-x divide-gray-200 dark:divide-gray-700 rounded-md shadow-sm overflow-hidden ring-1 ring-gray-200 dark:ring-gray-700 bg-white dark:bg-gray-900" role="group" title="Cohort mode only includes the campaigns present in the current table (current-period cohort).">
                    <button type="button" wire:click="setRoiPrevMode('full')" title="Full mode includes all previous-period campaigns for the account/overall."
                        class="px-2.5 py-1 text-[11px] font-medium focus:outline-none transition {{ $this->roiPrevMode === 'full' ? 'bg-amber-500 text-white' : 'bg-gray-50 text-gray-800 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }} rounded-l-md">
                        Full Totals
                    </button>
                    <button type="button" wire:click="setRoiPrevMode('cohort')" title="Cohort mode only includes the campaigns present in the current table (current-period cohort)."
                        class="px-2.5 py-1 text-[11px] font-medium focus:outline-none transition {{ $this->roiPrevMode === 'cohort' ? 'bg-amber-500 text-white' : 'bg-gray-50 text-gray-800 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }} rounded-r-md">
                        Cohort
                    </button>
                </div>
            </div>
        </div>
    </x-slot>
</x-filament-panels::page>

<script>
  window.GB_copyTable = window.GB_copyTable || function(table, dateStr) {
    if (!table) return;
    const headRow = table.querySelector('thead tr');
    const colCount = headRow ? Array.from(headRow.cells).reduce((a,c)=>a+(c.colSpan||1),0) : 8;
    const rows = Array.from(table.querySelectorAll('thead tr, tbody tr, tfoot tr'));
    const moneyToNum = s => (s||'').replace(/[^0-9.\-]/g,'');
    const escapeHtml = (s) => String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    const headHtmlRows = [];
    const bodyHtmlRows = [];
    const footHtmlRows = [];

    let groupStartRow = null;
    let groupEndRow = null;
    const accountSummaryRows = [];

    // Add date row at the top (row 1)
    const dateRow = new Array(colCount).fill('');
    dateRow[0] = dateStr || '';
    const dateHtmlRow = `<tr><td colspan="${colCount}" style="text-align:left;font-weight:bold;"><b>${escapeHtml(dateStr || '')}</b></td></tr>`;
    headHtmlRows.push(dateHtmlRow);

    const lines = [dateRow.join('\t')].concat(rows.map((tr, idx) => {
      const rowNumber = idx + 2; // date row is 1, header is 2, data starts at 3
      const isHead = tr.parentElement && tr.parentElement.tagName.toLowerCase()==='thead';
      const cells = Array.from(tr.cells);
      const parentTag = tr.parentElement ? tr.parentElement.tagName.toLowerCase() : '';
      const secondText = (cells[1] ? (cells[1].innerText||'') : '').replace(/\s+/g,' ').trim();
      const isAccountSummary = !isHead && parentTag==='tbody' && secondText.toLowerCase()==='account summary';
      const isSpacer = !isHead && parentTag==='tbody' && cells.length===1 && ((cells[0].colSpan||1)>= (headRow ? headRow.cells.length : 8));
      const isFoot = parentTag==='tfoot';
      const isSummaryRow = isFoot && secondText.toLowerCase()==='summary';
      const isDataRow = !isHead && !isAccountSummary && !isSpacer && !isFoot;
      if (isDataRow) {
        if (groupStartRow === null) groupStartRow = rowNumber;
        groupEndRow = rowNumber;
      }
      if (isAccountSummary) {
        accountSummaryRows.push(rowNumber);
      }
      const out = new Array(colCount).fill('');
      let htmlRow = `<tr${(isAccountSummary||isSummaryRow) ? ' style="font-weight:600;"' : ''}>`;
      const htmlCells = [];
      let ci = 0;
      cells.forEach(td => {
        const span = td.colSpan || 1;
        const rawOrig = (td.innerText||'').replace(/\s+/g,' ').trim();
        const tdBg = (td && td.style && td.style.backgroundColor) ? td.style.backgroundColor : '';
        for (let i=0; i<span && ci<colCount; i++) {
          if (i===0) {
            let val = rawOrig;
            if (!isHead) {
              const col = ci + 1; // 1-indexed
              // For Account Summary and SUMMARY rows, ROI Last (col 7) may not have innerText; use data-roi-prev fallback.
              if ((isAccountSummary || isSummaryRow) && col === 7) {
                const dp = td.getAttribute('data-roi-prev') || (td.dataset ? td.dataset.roiPrev : '') || '';
                if (!val && dp) val = dp;
              }
              // Normalize numeric $ columns (TOTAL SPEND=3, REVENUE=4, P/L=5)
              if (col===3 || col===4 || col===5) {
                val = moneyToNum(val) || '';
              }
              // P/L formula (col 5) => =D{row}-C{row}
              if (col===5) {
                val = rawOrig ? `=D${rowNumber}-C${rowNumber}` : '';
              }
              // ROI formula (col 6) => percent string using TEXT to ensure % on paste
              if (col===6) {
                val = rawOrig ? `=IF(C${rowNumber}>0, TEXT((D${rowNumber}/C${rowNumber})-1, "0.00%"), "")` : '';
              }
              // Account Summary Spend/Revenue formulas over the group's data rows
              if (isAccountSummary && groupStartRow !== null && groupEndRow !== null) {
                if (col===3) { // Spend
                  val = `=SUM(C${groupStartRow}:C${groupEndRow})`;
                }
                if (col===4) { // Revenue
                  val = `=SUM(D${groupStartRow}:D${groupEndRow})`;
                }
              }
              // Bottom SUMMARY row: sum all Account Summary cells in C/D
              if (isSummaryRow && accountSummaryRows.length) {
                if (col===3) {
                  const cRefs = accountSummaryRows.map(r => `C${r}`).join('+');
                  val = cRefs ? `=${cRefs}` : val;
                }
                if (col===4) {
                  const dRefs = accountSummaryRows.map(r => `D${r}`).join('+');
                  val = dRefs ? `=${dRefs}` : val;
                }
              }
            }
            out[ci] = val;

            const tag = isHead ? 'th' : 'td';
            let cellStyle = '';
            if (isHead) {
              cellStyle += 'background-color:#dadada;';
            } else if (tdBg) {
              cellStyle += `background-color:${tdBg};`;
            }
            if ((!isHead && (isAccountSummary || isSummaryRow) && ci === 1)) cellStyle += 'font-style:italic;';
            const col = ci + 1;
            const isFormulaCell = (!isHead && (
              col===5 || col===6 ||
              (isAccountSummary && (col===3 || col===4)) ||
              (isSummaryRow && (col===3 || col===4))
            ));
            // For HTML, apply the same ROI Last fallback for Account Summary/SUMMARY in col 7
            let rawForHtml = rawOrig;
            if (!isHead && (isAccountSummary || isSummaryRow) && col === 7 && !rawForHtml) {
              const dp = td.getAttribute('data-roi-prev') || (td.dataset ? td.dataset.roiPrev : '') || '';
              if (dp) rawForHtml = dp;
            }
            const htmlVal = isHead ? rawForHtml : (isFormulaCell ? val : rawForHtml);
            const escaped = escapeHtml(htmlVal);
            const labelBoldItalic = (!isHead && (isAccountSummary || isSummaryRow) && ci === 1);
            const content = labelBoldItalic ? `<b><i>${escaped}</i></b>` : escaped;
            htmlCells.push(`<${tag}${span>1 ? ` colspan="${span}"` : ''}${cellStyle ? ` style="${cellStyle}"` : ''}>${content}</${tag}>`);
          }
          ci++;
        }
      });
      htmlRow += htmlCells.join('') + '</tr>';
      if (isHead) headHtmlRows.push(htmlRow); else if (isFoot) footHtmlRows.push(htmlRow); else bodyHtmlRows.push(htmlRow);
      if (isAccountSummary) {
        groupStartRow = null;
        groupEndRow = null;
      }
      return out.join('\t');
    }));
    const tsv = lines.join('\n');
    const allBodyRows = bodyHtmlRows.concat(footHtmlRows);
    const html = `<table>${headHtmlRows.length?`<thead>${headHtmlRows.join('')}</thead>`:''}${allBodyRows.length?`<tbody>${allBodyRows.join('')}</tbody>`:''}</table>`;

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
        } catch(_) {}
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
          try { fallbackCopyMixed(); }
          catch(_) { fallbackCopyText(tsv); }
        }
      } catch (e) {
        try { fallbackCopyMixed(); }
        catch(_) { fallbackCopyText(tsv); }
      }
    })();
  };

  // Copy only Account Summary rows and the bottom SUMMARY with selected columns, no formulas
  window.GB_copySummary = window.GB_copySummary || function(table) {
    if (!table) return;
    const getText = (el) => (el?.innerText || '').replace(/\s+/g, ' ').trim();
    const headCells = Array.from(table.querySelectorAll('thead tr th'));
    const hdrRoiPrev = headCells[6] ? getText(headCells[6]) : 'ROI LAST WEEK';
    const headers = ['ACCOUNT NAME','TOTAL SPEND','REVENUE','P/L','ROI', hdrRoiPrev];

    const outRows = [];
    outRows.push(headers);

    const tbodyRows = Array.from(table.querySelectorAll('tbody tr')).filter(tr => tr.cells && tr.cells.length >= 6);
    tbodyRows.forEach(tr => {
      const cells = Array.from(tr.cells);
      const label = (cells[1] ? getText(cells[1]) : '').toLowerCase();
      if (label === 'account summary') {
        const account = getText(cells[0]);
        const spend = getText(cells[2]);
        const revenue = getText(cells[3]);
        const pl = getText(cells[4]);
        const roi = getText(cells[5]);
        const roiPrev = cells[6] ? getText(cells[6]) : '';
        outRows.push([account, spend, revenue, pl, roi, roiPrev]);
      }
    });

    const foot = table.querySelector('tfoot tr');
    if (foot && foot.cells && foot.cells.length >= 6) {
      const spend = getText(foot.cells[2]);
      const revenue = getText(foot.cells[3]);
      const pl = getText(foot.cells[4]);
      const roi = getText(foot.cells[5]);
      const roiPrev = foot.cells[6] ? getText(foot.cells[6]) : '';
      outRows.push(['SUMMARY', spend, revenue, pl, roi, roiPrev]);
    }

    // Build TSV
    const tsv = outRows.map(r => r.join('\t')).join('\n');

    // Build simple HTML table (preserves bold/italic and conditional backgrounds like COPY TABLE)
    const esc = (s) => String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\"/g,'&quot;');
    const toMoney = (s) => {
      if (!s) return NaN;
      let t = String(s).trim();
      let neg = false;
      if (t.includes('(') && t.includes(')')) { neg = true; t = t.replace(/[()]/g,''); }
      if (t.startsWith('-')) { neg = !neg || true; t = t.slice(1); }
      t = t.replace(/[$,\s]/g,'');
      const n = parseFloat(t);
      return isNaN(n) ? NaN : (neg ? -Math.abs(n) : n);
    };
    const toPercent = (s) => {
      if (!s) return NaN;
      let t = String(s).trim();
      let neg = false;
      if (t.includes('(') && t.includes(')')) { neg = true; t = t.replace(/[()]/g,''); }
      if (t.startsWith('-')) { neg = !neg || true; t = t.slice(1); }
      t = t.replace(/[%,\s]/g,'');
      const n = parseFloat(t);
      return isNaN(n) ? NaN : (neg ? -Math.abs(n) : n);
    };
    const headHtml = `<tr>${outRows[0].map(h => `<th style="background-color:#dadada;">${esc(h)}</th>`).join('')}</tr>`;
    const bodyHtml = outRows.slice(1).map(row => {
      const tds = row.map((v,i) => {
        const e = esc(v);
        // Columns: 0=Account,1=Spend,2=Revenue,3=P/L,4=ROI,5=ROI Last
        let style = '';
        if (i === 3) { // P/L
          const n = toMoney(v);
          if (!isNaN(n) && n !== 0) style = `background-color:${n>0?'#a3da9d':'#ff8080'};`;
        }
        if (i === 4 || i === 5) { // ROI / ROI Last
          const n = toPercent(v);
          if (!isNaN(n) && n !== 0) style = `background-color:${n>0?'#a3da9d':'#ff8080'};`;
        }
        if (i===0 && v === 'SUMMARY') return `<td${style?` style="${style}"`:''}><b><i>${e}</i></b></td>`;
        return `<td${style?` style="${style}"`:''}>${e}</td>`;
      }).join('');
      return `<tr>${tds}</tr>`;
    }).join('');
    const html = `<table><thead>${headHtml}</thead><tbody>${bodyHtml}</tbody></table>`;

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
        } catch(_) {}
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
          try { fallbackCopyMixed(); }
          catch(_) { fallbackCopyText(tsv); }
        }
      } catch (e) {
        try { fallbackCopyMixed(); }
        catch(_) { fallbackCopyText(tsv); }
      }
    })();
  };

  // Create Google Sheet for Google Binom report (8 columns)
  window.GB_createSheet = window.GB_createSheet || async function(table, dateStr, cadence, dateTo) {
    if (!table) return;

    // Open a tab immediately to avoid popup blockers; navigate later
    let pendingWin = null;
    try { pendingWin = window.open('', '_blank'); } catch (_) {}

    const headRow = table.querySelector('thead tr');
    const colCount = headRow ? Array.from(headRow.cells).reduce((a,c)=>a+(c.colSpan||1),0) : 8;
    const rows = Array.from(table.querySelectorAll('thead tr, tbody tr, tfoot tr'));
    const moneyToNum = s => (s||'').replace(/[^0-9.\-]/g,'');

    const values = [];
    // Date row at the top (row 1)
    const dateRow = new Array(colCount).fill('');
    dateRow[0] = dateStr || '';
    values.push(dateRow);

    // Track per-account ranges and account summary rows
    let groupStartRow = null;
    let groupEndRow = null;
    const accountSummaryRows = [];

    const isHeadTag = (tr) => tr.parentElement && tr.parentElement.tagName.toLowerCase()==='thead';

    rows.forEach((tr, idx) => {
      const rowNumber = idx + 2; // date row is 1
      const isHead = isHeadTag(tr);
      const cells = Array.from(tr.cells);
      const parentTag = tr.parentElement ? tr.parentElement.tagName.toLowerCase() : '';
      const secondText = (cells[1] ? (cells[1].innerText||'') : '').replace(/\s+/g,' ').trim();
      const isAccountSummary = !isHead && parentTag==='tbody' && secondText.toLowerCase()==='account summary';
      const isSpacer = !isHead && parentTag==='tbody' && cells.length===1 && ((cells[0].colSpan||1)>= (headRow ? headRow.cells.length : 8));
      const isFoot = parentTag==='tfoot';
      const isSummaryRow = isFoot && secondText.toLowerCase()==='summary';
      const isDataRow = !isHead && !isAccountSummary && !isSpacer && !isFoot;

      if (isDataRow) {
        if (groupStartRow === null) groupStartRow = rowNumber;
        groupEndRow = rowNumber;
      }
      if (isAccountSummary) accountSummaryRows.push(rowNumber);

      const out = new Array(colCount).fill('');
      cells.forEach((td, ci0) => {
        const span = td.colSpan || 1;
        const raw = (td.innerText||'').replace(/\s+/g,' ').trim();
        let ci = ci0;
        if (span > 1) ci = ci0; // only write into first spanned column
        if (ci < colCount) {
          let val = raw;
          if (!isHead) {
            const col = ci + 1; // 1-indexed
            // Normalize numeric $ columns: C(3), D(4), E(5)
            if (col===3 || col===4 || col===5) {
              val = moneyToNum(val) || '';
            }
            // P/L formula (E) = D - C
            if (col===5) {
              val = raw ? `=D${rowNumber}-C${rowNumber}` : '';
            }
            // ROI formula (F) numeric
            if (col===6) {
              val = raw ? `=IF(C${rowNumber}>0, (D${rowNumber}/C${rowNumber})-1, "")` : '';
            }
            // Account Summary: Spend/Revenue sums over group
            if (isAccountSummary && groupStartRow !== null && groupEndRow !== null) {
              if (col===3) val = `=SUM(C${groupStartRow}:C${groupEndRow})`;
              if (col===4) val = `=SUM(D${groupStartRow}:D${groupEndRow})`;
            }
            // Bottom SUMMARY row: sum Account Summary rows for C/D
            if (isSummaryRow && accountSummaryRows.length) {
              if (col===3) val = `=${accountSummaryRows.map(r => `C${r}`).join('+')}`;
              if (col===4) val = `=${accountSummaryRows.map(r => `D${r}`).join('+')}`;
            }
          }
          out[ci] = val;
        }
      });

      values.push(out);

      if (isAccountSummary) {
        groupStartRow = null;
        groupEndRow = null;
      }
    });

    try {
      const payload = {
        title: `${dateStr || ''} - Google Ads`,
        values,
        cadence,
        date_to: dateTo,
      };

      const resp = await fetch('{{ route('google.sheets.google_binom.create') }}', {
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
          const onMsg = async (ev) => {
            try {
              if (!ev) return;
              if (ev.origin !== window.location.origin) return;
              if (!ev.data || ev.data.type !== 'google-auth-success') return;
              window.removeEventListener('message', onMsg);
              const retry = await fetch('{{ route('google.sheets.google_binom.create') }}', {
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

          const authUrl = data.authorizeUrl;
          if (pendingWin && !pendingWin.closed) pendingWin.location.href = authUrl; else {
            try { pendingWin = window.open(authUrl, '_blank'); } catch (_) {}
          }
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

  // Build values array for Google Sheet (same as GB_createSheet but reusable)
  window.GB_buildSheetValues = window.GB_buildSheetValues || function(table, dateStr) {
    if (!table) return [];
    const headRow = table.querySelector('thead tr');
    const colCount = headRow ? Array.from(headRow.cells).reduce((a,c)=>a+(c.colSpan||1),0) : 8;
    const rows = Array.from(table.querySelectorAll('thead tr, tbody tr, tfoot tr'));
    const moneyToNum = s => (s||'').replace(/[^0-9.\-]/g,'');
    const values = [];
    // Date row at the top (row 1)
    const dateRow = new Array(colCount).fill('');
    dateRow[0] = dateStr || '';
    values.push(dateRow);
    let groupStartRow = null, groupEndRow = null;
    const accountSummaryRows = [];
    const isHeadTag = (tr) => tr.parentElement && tr.parentElement.tagName.toLowerCase()==='thead';
    rows.forEach((tr, idx) => {
      const rowNumber = idx + 2; // date row is 1
      const isHead = isHeadTag(tr);
      const cells = Array.from(tr.cells);
      const parentTag = tr.parentElement ? tr.parentElement.tagName.toLowerCase() : '';
      const secondText = (cells[1] ? (cells[1].innerText||'') : '').replace(/\s+/g,' ').trim();
      const isAccountSummary = !isHead && parentTag==='tbody' && secondText.toLowerCase()==='account summary';
      const isSpacer = !isHead && parentTag==='tbody' && cells.length===1 && ((cells[0].colSpan||1)>= (headRow ? headRow.cells.length : 8));
      const isFoot = parentTag==='tfoot';
      const isSummaryRow = isFoot && secondText.toLowerCase()==='summary';
      const isDataRow = !isHead && !isAccountSummary && !isSpacer && !isFoot;
      if (isDataRow) { if (groupStartRow === null) groupStartRow = rowNumber; groupEndRow = rowNumber; }
      if (isAccountSummary) accountSummaryRows.push(rowNumber);
      const out = new Array(colCount).fill('');
      cells.forEach((td, ci0) => {
        const span = td.colSpan || 1;
        const raw = (td.innerText||'').replace(/\s+/g,' ').trim();
        let ci = ci0;
        if (span > 1) ci = ci0; // only write into first spanned column
        if (ci < colCount) {
          let val = raw;
          if (!isHead) {
            const col = ci + 1; // 1-indexed
            // Normalize numeric $ columns: C(3), D(4), E(5)
            if (col===3 || col===4 || col===5) { val = moneyToNum(val) || ''; }
            // P/L formula (E) = D - C
            if (col===5) { val = raw ? `=D${rowNumber}-C${rowNumber}` : ''; }
            // ROI formula (F) numeric
            if (col===6) { val = raw ? `=IF(C${rowNumber}>0, (D${rowNumber}/C${rowNumber})-1, "")` : ''; }
            // Account Summary: Spend/Revenue sums over group
            if (isAccountSummary && groupStartRow !== null && groupEndRow !== null) {
              if (col===3) val = `=SUM(C${groupStartRow}:C${groupEndRow})`;
              if (col===4) val = `=SUM(D${groupStartRow}:D${groupEndRow})`;
            }
            // Bottom SUMMARY row: sum Account Summary rows for C/D
            if (isSummaryRow && accountSummaryRows.length) {
              if (col===3) val = `=${accountSummaryRows.map(r => `C${r}`).join('+')}`;
              if (col===4) val = `=${accountSummaryRows.map(r => `D${r}`).join('+')}`;
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
  window.GB_createSheetSilently = window.GB_createSheetSilently || async function(table, dateStr, cadence, dateTo) {
    try {
      const values = GB_buildSheetValues(table, dateStr);
      const payload = { title: `${dateStr || ''} - Google Ads`, values, cadence, date_to: dateTo };
      const postOnce = async () => fetch('{{ route('google.sheets.google_binom.create') }}', {
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

  // Extract SUMMARY table (account summaries + bottom summary) as HTML only (no formulas)
  window.GB_extractSummaryHtml = window.GB_extractSummaryHtml || function(table) {
    if (!table) return '<table></table>';
    const getText = (el) => (el?.innerText || '').replace(/\s+/g, ' ').trim();
    const headCells = Array.from(table.querySelectorAll('thead tr th'));
    const hdrRoiPrev = headCells[6] ? getText(headCells[6]) : 'ROI LAST WEEK';
    const headers = ['ACCOUNT NAME','TOTAL SPEND','REVENUE','P/L','ROI', hdrRoiPrev];
    const outRows = [];
    outRows.push(headers);
    const tbodyRows = Array.from(table.querySelectorAll('tbody tr')).filter(tr => tr.cells && tr.cells.length >= 6);
    const getCellText = (td) => (td?.innerText || '').replace(/\s+/g, ' ').trim();
    tbodyRows.forEach(tr => {
      const cells = Array.from(tr.cells);
      const label = (cells[1] ? getCellText(cells[1]) : '').toLowerCase();
      if (label === 'account summary') {
        const account = getCellText(cells[0]);
        const spend = getCellText(cells[2]);
        const revenue = getCellText(cells[3]);
        const pl = getCellText(cells[4]);
        const roi = getCellText(cells[5]);
        const roiPrev = cells[6] ? getCellText(cells[6]) : '';
        outRows.push([account, spend, revenue, pl, roi, roiPrev]);
      }
    });
    const foot = table.querySelector('tfoot tr');
    if (foot && foot.cells && foot.cells.length >= 6) {
      const spend = getCellText(foot.cells[2]);
      const revenue = getCellText(foot.cells[3]);
      const pl = getCellText(foot.cells[4]);
      const roi = getCellText(foot.cells[5]);
      const roiPrev = foot.cells[6] ? getCellText(foot.cells[6]) : '';
      outRows.push(['SUMMARY', spend, revenue, pl, roi, roiPrev]);
    }
    const esc = (s) => String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\"/g,'&quot;');
    const toMoney = (s) => {
      if (!s) return NaN; let t = String(s).trim(); let neg = false; if (t.includes('(') && t.includes(')')) { neg = true; t = t.replace(/[()]/g,''); }
      if (t.startsWith('-')) { neg = !neg || true; t = t.slice(1); } t = t.replace(/[$,\s]/g,''); const n = parseFloat(t); return isNaN(n) ? NaN : (neg ? -Math.abs(n) : n);
    };
    const toPercent = (s) => {
      if (!s) return NaN; let t = String(s).trim(); let neg = false; if (t.includes('(') && t.includes(')')) { neg = true; t = t.replace(/[()]/g,''); }
      if (t.startsWith('-')) { neg = !neg || true; t = t.slice(1); } t = t.replace(/[%,\s]/g,''); const n = parseFloat(t); return isNaN(n) ? NaN : (neg ? -Math.abs(n) : n);
    };
    const borderCell = 'border:1px solid #bfbfbf;padding:6px 8px;';
    const headHtml = `<tr>${outRows[0].map(h => `<th style="background-color:#dadada;${borderCell}">${esc(h)}</th>`).join('')}</tr>`;
    const bodyHtml = outRows.slice(1).map(row => {
      const tds = row.map((v,i) => {
        const e = esc(v);
        let style = borderCell;
        if (i === 3) { const n = toMoney(v); if (!isNaN(n) && n !== 0) style += `background-color:${n>0?'#a3da9d':'#ff8080'};`; }
        if (i === 4 || i === 5) { const n = toPercent(v); if (!isNaN(n) && n !== 0) style += `background-color:${n>0?'#a3da9d':'#ff8080'};`; }
        if (i===0 && v === 'SUMMARY') return `<td style="${style}"><b><i>${e}</i></b></td>`;
        return `<td style="${style}">${e}</td>`;
      }).join('');
      return `<tr>${tds}</tr>`;
    }).join('');
    return `<table style="border-collapse:collapse;border:1px solid #bfbfbf;"><thead>${headHtml}</thead><tbody>${bodyHtml}</tbody></table>`;
  };

  // Create Gmail Draft for Google Binom: build SUMMARY HTML, ensure Sheet exists, then call Gmail draft endpoint
  window.GB_createDraft = window.GB_createDraft || async function(table, dateStr, cadence, dateTo, dateFrom) {
    if (!table) return;
    try {
      // 1) Build SUMMARY HTML for email body
      const tableHtml = GB_extractSummaryHtml(table);
      // 2) Ensure a Google Sheet is created to link in the email
      const sheetUrl = await GB_createSheetSilently(table, dateStr, cadence, dateTo);
      // 3) Compose body
      const cadenceLabel = cadence ? (cadence.charAt(0).toUpperCase() + cadence.slice(1)) : 'Weekly';
      const fmtDMYdot = (iso) => {
        try {
          const d = new Date(iso);
          if (!d || isNaN(d.getTime())) return iso || '';
          const dd = String(d.getDate()).padStart(2,'0');
          const mm = String(d.getMonth()+1).padStart(2,'0');
          const yyyy = d.getFullYear();
          return `${dd}.${mm}.${yyyy}`;
        } catch (_) { return iso || ''; }
      };
      const rangeText = `${fmtDMYdot(dateFrom)} - ${fmtDMYdot(dateTo)}`.trim();
      const linkText = rangeText || (dateStr || 'the selected period');
      const linkedPhrase = sheetUrl ? `<a href="${sheetUrl}" target="_blank" rel="noopener">${linkText}</a>` : linkText;
      const bodyTop = `<p style="margin:0 0 12px 0;">Hello Jesse,</p>` +
                      `<p style="margin:0 0 16px 0;">Here is the Google ${cadenceLabel} Report from ${linkedPhrase}.</p>`;
      const bodyBottom = `<p style=\"margin:16px 0 0 0;\">Thanks,<br/>Allan</p>`;
      const html = `<div style=\"font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.4;\">${bodyTop}${tableHtml}${bodyBottom}</div>`;
      // 4) Call Gmail draft endpoint
      const payload = { html: html, cadence, date_to: dateTo, date_from: dateFrom || null, date_str: dateStr || '', sheet_url: sheetUrl || '', is_full_body: true };
      const postOnce = async () => fetch('{{ route('google.gmail.google_binom.create_draft') }}', {
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
              try { if (ev.origin !== window.location.origin) return; if (!ev.data || ev.data.type !== 'google-auth-success') return; window.removeEventListener('message', onMsg); resolve(); } catch (e) { reject(e); }
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

</script>
