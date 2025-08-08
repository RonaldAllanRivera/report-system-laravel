@php
    $sections = $this->buildGroupedByUploadDate();
    $filters = $this->filters;
@endphp

<x-filament-panels::page>
    <div class="space-y-3">
        @forelse($sections as $section)
            @php($report = $section['report'])
            <div x-data="{ open: false }" class="rounded-lg border bg-white">
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
                        <svg x-bind:class="open ? 'rotate-180' : ''" class="h-4 w-4 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                </div>
                <div x-show="open" x-collapse class="overflow-x-auto">
                    @if($report['has_rumble'])
                        <table class="min-w-full text-xs">
                            <thead class="bg-gray-50">
                                <tr class="text-left">
                                    <th class="px-3 py-2 font-medium text-gray-600">Account</th>
                                    <th class="px-3 py-2 font-medium text-gray-600">Campaign</th>
                                    <th class="px-3 py-2 font-medium text-gray-600">Daily Cap</th>
                                    <th class="px-3 py-2 font-medium text-gray-600">Spend</th>
                                    <th class="px-3 py-2 font-medium text-gray-600">Revenue</th>
                                    <th class="px-3 py-2 font-medium text-gray-600">P/L</th>
                                    <th class="px-3 py-2 font-medium text-gray-600">ROI</th>
                                    <th class="px-3 py-2 font-medium text-gray-600">Conv.</th>
                                    <th class="px-3 py-2 font-medium text-gray-600">CPM</th>
                                    <th class="px-3 py-2 font-medium text-gray-600">Set CPM</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($report['groups'] as $group)
                                    @foreach($group['rows'] as $row)
                                        <tr class="border-t">
                                            <td class="px-3 py-2 text-gray-600">{{ $row['account'] }}</td>
                                            <td class="px-3 py-2 text-gray-600">{{ $row['campaign_name'] }}</td>
                                            <td class="px-3 py-2 text-gray-600">{{ $this->fmtMoney($row['daily_cap'] ?? 0) }}</td>
                                            <td class="px-3 py-2 text-gray-600">{{ $this->fmtMoney($row['spend']) }}</td>
                                            <td class="px-3 py-2 text-gray-600">{{ $this->fmtMoney($row['revenue']) }}</td>
                                            <td class="px-3 py-2 text-gray-600">{{ $this->fmtMoney($row['pl']) }}</td>
                                            <td class="px-3 py-2 text-gray-600">{{ $this->fmtPercent($row['roi']) }}</td>
                                            <td class="px-3 py-2 text-gray-600">{{ $row['conversions'] ?? '' }}</td>
                                            <td class="px-3 py-2 text-gray-600">{{ $row['cpm'] !== null ? $this->fmtMoney($row['cpm']) : '' }}</td>
                                            <td class="px-3 py-2 text-gray-600">{{ $row['set_cpm'] !== null ? $this->fmtMoney($row['set_cpm']) : '' }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="border-t bg-gray-50">
                                        <td class="px-3 py-2 text-gray-700">{{ $group['account'] }}</td>
                                        <td class="px-3 py-2 italic text-gray-700">Account Summary</td>
                                        <td class="px-3 py-2"></td>
                                        <td class="px-3 py-2 font-semibold text-gray-700">{{ $this->fmtMoney($group['summary']['spend'] ?? 0) }}</td>
                                        <td class="px-3 py-2 font-semibold text-gray-700">{{ $this->fmtMoney($group['summary']['revenue'] ?? 0) }}</td>
                                        <td class="px-3 py-2 font-semibold text-gray-700">{{ $this->fmtMoney(($group['summary']['pl'] ?? 0)) }}</td>
                                        <td class="px-3 py-2 font-semibold text-gray-700">{{ $this->fmtPercent($group['summary']['roi'] ?? null) }}</td>
                                        <td class="px-3 py-2"></td>
                                        <td class="px-3 py-2"></td>
                                        <td class="px-3 py-2"></td>
                                    </tr>
                                    <tr class="border-0">
                                        <td colspan="10" class="px-3 py-2"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr class="border-t">
                                    <td class="px-3 py-2 text-gray-700"></td>
                                    <td class="px-3 py-2 italic text-gray-700">SUMMARY</td>
                                    <td class="px-3 py-2"></td>
                                    <td class="px-3 py-2 font-semibold text-gray-700">{{ $this->fmtMoney($report['totals']['spend'] ?? 0) }}</td>
                                    <td class="px-3 py-2 font-semibold text-gray-700">{{ $this->fmtMoney($report['totals']['revenue'] ?? 0) }}</td>
                                    <td class="px-3 py-2 font-semibold text-gray-700">{{ $this->fmtMoney($report['totals']['pl'] ?? 0) }}</td>
                                    <td class="px-3 py-2 font-semibold text-gray-700">{{ $this->fmtPercent($report['totals']['roi'] ?? null) }}</td>
                                    <td class="px-3 py-2"></td>
                                    <td class="px-3 py-2"></td>
                                    <td class="px-3 py-2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    @else
                        <div class="px-4 py-3 text-xs text-gray-600">No Rumble Data for this date range.</div>
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
                <h2 class="text-lg font-semibold">Rumble Binom Report</h2>
                <p class="text-xs text-gray-600">Type: <span class="font-medium">{{ strtoupper($filters['report_type'] ?? 'daily') }}</span></p>
            </div>

            <div class="flex items-center gap-2">
                <x-filament-actions::actions :actions="$this->getHeaderActions()" class="shrink-0"/>
                <x-filament-actions::modals />
            </div>
        </div>
    </x-slot>
</x-filament-panels::page>
