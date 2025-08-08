@php
    $groupedData = $this->getGroupedCampaignData();
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        @foreach($groupedData as $date => $items)
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <button 
                    x-data
                    @click="$el.nextElementSibling.classList.toggle('hidden')"
                    class="w-full px-4 py-3 text-left bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition flex items-center justify-between"
                >
                    <div class="font-medium">
                        {{ \Carbon\Carbon::parse($date)->format('F j, Y') }}
                        <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">
                            ({{ $items->count() }} {{ \Illuminate\Support\Str::plural('campaign', $items->count()) }})
                        </span>
                    </div>
                </button>
                
                <div class="hidden overflow-x-auto bg-white dark:bg-gray-800">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">CPM</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Daily Limit</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($items as $item)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ $item->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        ${{ number_format($item->cpm, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        @if(!is_null($item->daily_limit))
                                            ${{ number_format($item->daily_limit, 0) }}
                                        @else
                                            —
                                        @endif
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
