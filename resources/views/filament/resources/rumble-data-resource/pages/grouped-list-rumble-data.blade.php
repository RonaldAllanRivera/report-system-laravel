@php
    $groupedData = $this->getGroupedRumbleData();
@endphp
<x-filament-panels::page>
    <div class="space-y-6">
        @foreach($groupedData as $rangeKey => $items)
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div 
                    x-data="{ open: false }"
                    @click="open = !open; $refs.body.classList.toggle('hidden')"
                    role="button"
                    tabindex="0"
                    class="w-full px-4 py-3 text-left bg-gray-50 dark:bg-gray-800 hover:bg-gray-300 dark:hover:bg-gray-950 transition-colors duration-150 flex items-center justify-between cursor-pointer"
                    data-qa="rumble-header-v2"
                >
                    <div class="font-medium">
                        @php
                            [$fromRaw, $toRaw] = array_pad(explode('|', $rangeKey, 2), 2, null);
                            $from = $fromRaw ? \Carbon\Carbon::parse($fromRaw) : null;
                            $to = $toRaw ? \Carbon\Carbon::parse($toRaw) : null;
                        @endphp
                        @if($from && $to)
                            @if($from->isSameDay($to))
                                {{ $from->format('F j, Y') }}
                            @else
                                {{ $from->format('F j') }} – {{ $to->format($from->isSameYear($to) ? 'F j, Y' : 'F j, Y') }}
                            @endif
                        @elseif($from)
                            {{ $from->format('F j, Y') }}
                        @elseif($to)
                            {{ $to->format('F j, Y') }}
                        @endif
                        <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">
                            ({{ $items->count() }} {{ Str::plural('campaign', $items->count()) }})
                        </span>
                    </div>
                    <div class="flex items-center">
                        <span class="text-gray-500 dark:text-gray-400 mr-2">
                            ${{ number_format($items->sum('spend'), 2) }} spent
                        </span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform transform" 
                             :class="{ 'rotate-180': open }" 
                             fill="none" 
                             viewBox="0 0 24 24" 
                             stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 rounded-md bg-red-600 px-2 py-1 text-xs font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600"
                            x-on:click.stop
                            wire:click="deleteRange('{{ $rangeKey }}')"
                            title="Delete this group's data"
                        >
                            <!-- trash icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M16.5 4.478V4.5A2.25 2.25 0 0 1 14.25 6.75h-4.5A2.25 2.25 0 0 1 7.5 4.5v-.022a48.474 48.474 0 0 1 9 0ZM4.5 7.5a.75.75 0 0 1 .75-.75h13.5a.75.75 0 0 1 0 1.5H18l-.615 9.226A3.75 3.75 0 0 1 13.644 21H10.356a3.75 3.75 0 0 1-3.741-3.274L6 8.25H5.25A.75.75 0 0 1 4.5 7.5Z" clip-rule="evenodd"/></svg>
                            Delete
                        </button>
                    </div>
                </div>
                
                <div x-ref="body" class="hidden overflow-x-auto bg-white dark:bg-gray-800">
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
                                <tr>
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
