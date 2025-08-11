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
            <div x-data="{ open: false, copying: false }" class="rounded-lg border bg-white" wire:key="section-{{ $section['report_type'] }}-{{ $section['date_from'] }}-{{ $section['date_to'] }}">
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
                        <button type="button" @click.stop="GB_copyTable($refs.tbl); copying = true; setTimeout(()=>copying=false, 1200)" title="Copy this table to clipboard"
                            class="ml-2 text-red-600 hover:text-red-700 font-semibold text-xs uppercase tracking-wide {{ $report['has_rows'] ? '' : 'opacity-40 pointer-events-none' }}">
                            <span x-show="!copying">COPY TABLE</span>
                            <span x-show="copying" class="text-green-600">COPIED</span>
                        </button>
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
                                    <th class="px-3 py-2 font-medium text-gray-600">{{ $rt === 'weekly' ? 'ROI LAST WEEK' : 'ROI LAST MONTH' }}</th>
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
                                            <td class="px-3 py-2 text-gray-600">{{ $this->fmtPercent($row['roi_prev']) }}</td>
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
                                        <td class="px-3 py-2"></td>
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
            <div class="rounded-lg border bg-white p-4 text-sm text-gray-600">No uploads found for the selected report type.</div>
        @endforelse
    </div>

    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <h2 class="text-lg font-semibold">Google Binom Report</h2>
                <p class="text-xs text-gray-600">Type: <span class="font-medium">{{ strtoupper($filters['report_type'] ?? 'weekly') }}</span></p>
            </div>
            <div class="flex items-center gap-2"></div>
        </div>
    </x-slot>
</x-filament-panels::page>

<script>
  window.GB_copyTable = window.GB_copyTable || function(table) {
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

    const lines = rows.map((tr, idx) => {
      const rowNumber = idx + 1;
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
        const raw = (td.innerText||'').replace(/\s+/g,' ').trim();
        const tdBg = (td && td.style && td.style.backgroundColor) ? td.style.backgroundColor : '';
        for (let i=0; i<span && ci<colCount; i++) {
          if (i===0) {
            let val = raw;
            if (!isHead) {
              const col = ci + 1; // 1-indexed
              // Normalize numeric $ columns (TOTAL SPEND=3, REVENUE=4, P/L=5)
              if (col===3 || col===4 || col===5) {
                val = moneyToNum(val) || '';
              }
              // P/L formula (col 5) => =D{row}-C{row}
              if (col===5) {
                val = raw ? `=D${rowNumber}-C${rowNumber}` : '';
              }
              // ROI formula (col 6) => =(D{row}/C{row})-1
              if (col===6) {
                val = raw ? `=(D${rowNumber}/C${rowNumber})-1` : '';
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
            if (tdBg) cellStyle += `background-color:${tdBg};`;
            if ((!isHead && (isAccountSummary || isSummaryRow) && ci === 1)) cellStyle += 'font-style:italic;';
            const col = ci + 1;
            const isFormulaCell = (!isHead && (
              col===5 || col===6 ||
              (isAccountSummary && (col===3 || col===4)) ||
              (isSummaryRow && (col===3 || col===4))
            ));
            const htmlVal = isHead ? raw : (isFormulaCell ? val : raw);
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
    });
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
</script>
