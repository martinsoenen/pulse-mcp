<x-pulse::card :cols="$cols" :rows="$rows" :class="$class">
    <x-pulse::card-header
        name="MCP Tool Usage"
        x-bind:title="`Time: {{ number_format($time) }}ms; Run at: ${formatDate('{{ $runAt }}')};`"
        details="past {{ $this->periodForHumans() }}"
    >
        <x-slot:icon>
            <x-pulse::icons.command-line />
        </x-slot:icon>
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
                        <x-pulse::th class="text-right">Calls</x-pulse::th>
                        <x-pulse::th class="text-right">Errors</x-pulse::th>
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
                                    <span title="Sample rate: {{ $sampleRate }}, Raw value: {{ number_format($tool->calls) }}">~{{ number_format($tool->calls * (1 / $sampleRate)) }}</span>
                                @else
                                    {{ number_format($tool->calls) }}
                                @endif
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-700 dark:text-gray-300 font-bold">
                                @if ($sampleRate < 1)
                                    <span title="Sample rate: {{ $sampleRate }}, Raw value: {{ number_format($tool->errors) }}">~{{ number_format($tool->errors * (1 / $sampleRate)) }}</span>
                                @else
                                    {{ number_format($tool->errors) }}
                                @endif
                            </x-pulse::td>
                        </tr>
                    @endforeach
                </tbody>
            </x-pulse::table>
        @endif
    </x-pulse::scroll>
</x-pulse::card>
