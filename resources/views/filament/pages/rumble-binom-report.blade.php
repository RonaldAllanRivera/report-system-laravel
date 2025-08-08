@php
    $data = $this->buildReportData();
    $filters = $this->filters;
@endphp

<x-filament-panels::page>
    <div class="rounded-lg border bg-white">
        <div class="p-3 border-b bg-gray-50 flex items-center justify-between">
            <div class="text-sm font-medium text-gray-700">Totals</div>
            <div class="text-xs text-gray-700 flex items-center gap-4">
                <span>Spend: <span class="font-semibold">{{ $this->fmtMoney($data['totals']['spend'] ?? 0) }}</span></span>
                <span>Revenue: <span class="font-semibold">{{ $this->fmtMoney($data['totals']['revenue'] ?? 0) }}</span></span>
                <span>P/L: <span class="font-semibold">{{ $this->fmtMoney($data['totals']['pl'] ?? 0) }}</span></span>
                <span>ROI: <span class="font-semibold">{{ $this->fmtPercent($data['totals']['roi'] ?? null) }}</span></span>
            </div>
        </div>

        <div class="divide-y">
            @forelse($data['groups'] as $group)
                <div x-data="{ open: true }" class="">
                    <div class="p-3 flex items-center justify-between cursor-pointer hover:bg-gray-50" @click="open = !open">
                        <div class="text-sm font-semibold text-gray-700">{{ $group['account'] }}</div>
                        <div class="text-xs text-gray-700 flex items-center gap-4">
                            <span>Spend: <span class="font-semibold">{{ $this->fmtMoney($group['summary']['spend'] ?? 0) }}</span></span>
                            <span>Revenue: <span class="font-semibold">{{ $this->fmtMoney($group['summary']['revenue'] ?? 0) }}</span></span>
                            <span>P/L: <span class="font-semibold">{{ $this->fmtMoney($group['summary']['pl'] ?? 0) }}</span></span>
                            <span>ROI: <span class="font-semibold">{{ $this->fmtPercent($group['summary']['roi'] ?? null) }}</span></span>
                        </div>
                    </div>

                    <div x-show="open" x-cloak class="overflow-x-auto">
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
                                @foreach($group['rows'] as $row)
                                    <tr class="border-t">
                                        <td class="px-3 py-2 text-gray-600">{{ $row['account'] }}</td>
                                        <td class="px-3 py-2 text-gray-600">{{ $row['campaign_name'] }}</td>
                                        <td class="px-3 py-2 text-gray-600">{{ $row['daily_cap'] ?? '' }}</td>
                                        <td class="px-3 py-2 text-gray-600">{{ $this->fmtMoney($row['spend']) }}</td>
                                        <td class="px-3 py-2 text-gray-600">{{ $this->fmtMoney($row['revenue']) }}</td>
                                        <td class="px-3 py-2 text-gray-600">{{ $this->fmtMoney($row['pl']) }}</td>
                                        <td class="px-3 py-2 text-gray-600">{{ $this->fmtPercent($row['roi']) }}</td>
                                        <td class="px-3 py-2 text-gray-600">{{ $row['conversions'] ?? '' }}</td>
                                        <td class="px-3 py-2 text-gray-600">{{ $row['cpm'] !== null ? number_format($row['cpm'], 2) : '' }}</td>
                                        <td class="px-3 py-2 text-gray-600">{{ $row['set_cpm'] !== null ? number_format($row['set_cpm'], 2) : '' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="p-4 text-sm text-gray-600">No data for selected range.</div>
            @endforelse
        </div>
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
