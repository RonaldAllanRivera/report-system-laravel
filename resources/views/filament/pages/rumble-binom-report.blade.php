@php
    $data = $this->buildReportData();
    $filters = $this->filters;
@endphp

<x-filament-panels::page>
    <div class="rounded-lg border bg-white overflow-x-auto">
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
                @forelse($data['groups'] as $group)
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
                    {{-- Account Summary row (below campaigns) --}}
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
                    {{-- Spacer row --}}
                    <tr class="border-0">
                        <td colspan="10" class="px-3 py-2"></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="p-4 text-sm text-gray-600">No data for selected range.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="bg-gray-50">
                <tr class="border-t">
                    <td class="px-3 py-2 text-gray-700"></td>
                    <td class="px-3 py-2 italic text-gray-700">SUMMARY</td>
                    <td class="px-3 py-2"></td>
                    <td class="px-3 py-2 font-semibold text-gray-700">{{ $this->fmtMoney($data['totals']['spend'] ?? 0) }}</td>
                    <td class="px-3 py-2 font-semibold text-gray-700">{{ $this->fmtMoney($data['totals']['revenue'] ?? 0) }}</td>
                    <td class="px-3 py-2 font-semibold text-gray-700">{{ $this->fmtMoney($data['totals']['pl'] ?? 0) }}</td>
                    <td class="px-3 py-2 font-semibold text-gray-700">{{ $this->fmtPercent($data['totals']['roi'] ?? null) }}</td>
                    <td class="px-3 py-2"></td>
                    <td class="px-3 py-2"></td>
                    <td class="px-3 py-2"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <h2 class="text-lg font-semibold">Rumble - Binom Report</h2>
                <p class="text-xs text-gray-600">
                    Type: <span class="font-medium">{{ strtoupper($filters['report_type'] ?? 'daily') }}</span>
                    • Date: <span class="font-medium">
                        @if(($filters['date_from'] ?? '') === ($filters['date_to'] ?? ''))
                            {{ \Illuminate\Support\Carbon::parse($filters['date_from'])->format('M d, Y') }}
                        @else
                            {{ \Illuminate\Support\Carbon::parse($filters['date_from'])->format('M d, Y') }} -
                            {{ \Illuminate\Support\Carbon::parse($filters['date_to'])->format('M d, Y') }}
                        @endif
                    </span>
                </p>
            </div>

            <div class="flex items-center gap-2">
                <x-filament-actions::actions :actions="$this->getHeaderActions()" class="shrink-0"/>
                <x-filament-actions::modals />
            </div>
        </div>
    </x-slot>
</x-filament-panels::page>
