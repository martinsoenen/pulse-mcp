<x-pulse::card :cols="$cols" :rows="$rows" :class="$class">
    <x-pulse::card-header
        name="Slow MCP Tools"
        x-bind:title="`Time: {{ number_format($time) }}ms; Run at: ${formatDate('{{ $runAt }}')};`"
        details="{{ is_array($threshold) ? '' : $threshold.'ms threshold, ' }}past {{ $this->periodForHumans() }}"
    >
        <x-slot:icon>
            <x-pulse::icons.clock />
        </x-slot:icon>
        <x-slot:actions>
            <x-pulse::select
                wire:model.live="orderBy"
                id="select-slow-mcp-tools-order-by"
                label="Sort by"
                :options="[
                    'slowest' => 'slowest',
                    'count' => 'count',
                ]"
                @change="loading = true"
            />
        </x-slot:actions>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand" wire:poll.5s="">
        @if ($tools->isEmpty())
            <x-pulse::no-results />
        @else
            <x-pulse::table>
                <colgroup>
                    <col width="100%" />
                    <col width="0%" />
                    <col width="0%" />
                </colgroup>
                <x-pulse::thead>
                    <tr>
                        <x-pulse::th>Tool</x-pulse::th>
                        <x-pulse::th class="text-right">Count</x-pulse::th>
                        <x-pulse::th class="text-right">Slowest</x-pulse::th>
                    </tr>
                </x-pulse::thead>
                <tbody>
                    @foreach ($tools->take(100) as $tool)
                        <tr wire:key="{{ $tool->tool }}">
                            <x-pulse::td class="max-w-[1px] truncate font-mono text-xs text-gray-900 dark:text-gray-100" title="{{ $tool->tool }}">
                                {{ $tool->tool }}
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-700 dark:text-gray-300 font-bold">
                                @if ($sampleRate < 1)
                                    <span title="Sample rate: {{ $sampleRate }}, Raw value: {{ number_format($tool->count) }}">~{{ number_format($tool->count * (1 / $sampleRate)) }}</span>
                                @else
                                    {{ number_format($tool->count) }}
                                @endif
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-700 dark:text-gray-300">
                                <strong>{{ number_format($tool->slowest) ?: '<1' }}</strong> ms
                            </x-pulse::td>
                        </tr>
                    @endforeach
                </tbody>
            </x-pulse::table>
        @endif

        @if ($tools->count() > 100)
            <div class="mt-2 text-xs text-gray-400 text-center">Limited to 100 entries</div>
        @endif
    </x-pulse::scroll>
</x-pulse::card>
