@php
    $groupedData = $this->getGroupedRumbleData();
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        @foreach($groupedData as $date => $items)
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <button 
                    @click="$el.nextElementSibling.classList.toggle('hidden')"
                    class="w-full px-4 py-3 text-left bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition flex items-center justify-between"
                >
                    <div class="font-medium">
                        {{ \Carbon\Carbon::parse($date)->format('F j, Y') }}
                        <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">
                            ({{ $items->count() }} {{ Str::plural('campaign', $items->count()) }})
                        </span>
                    </div>
                    <div class="flex items-center">
                        <span class="text-gray-500 dark:text-gray-400 mr-2">
                            ${{ number_format($items->sum('spend'), 2) }} spent
                        </span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform transform" 
                             :class="{ 'rotate-180': !$el.nextElementSibling.classList.contains('hidden') }" 
                             fill="none" 
                             viewBox="0 0 24 24" 
                             stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </button>
                
                <div class="hidden overflow-x-auto bg-white dark:bg-gray-800">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Campaign</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Spend</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">CPM</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($items as $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ $item->campaign }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        ${{ number_format($item->spend, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        ${{ number_format($item->cpm, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $colors = [
                                                'daily' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                                'weekly' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                                'monthly' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                            ][$item->report_type] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
                                        @endphp
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $colors }}">
                                            {{ ucfirst($item->report_type) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
