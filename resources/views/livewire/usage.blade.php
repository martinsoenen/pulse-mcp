<x-pulse::card :cols="$cols" :rows="$rows" :class="$class">
    <x-pulse::card-header
        :name="match ($this->type) {
            'calls' => 'Top 10 Users Calling MCP Tools',
            'slow_calls' => 'Top 10 Users Experiencing Slow MCP Tools',
            default => 'MCP Usage'
        }"
        x-bind:title="`Time: {{ number_format($time) }}ms; Run at: ${formatDate('{{ $runAt }}')};`"
        details="{{ ($this->type ?? $this->usage) === 'slow_calls' ? (is_array($threshold) ? '' : $threshold.'ms threshold, ') : '' }}past {{ $this->periodForHumans() }}"
    >
        <x-slot:icon>
            <x-dynamic-component :component="'pulse::icons.' . match ($this->type) {
                'calls' => 'arrow-trending-up',
                'slow_calls' => 'clock',
                default => 'cursor-arrow-rays'
            }" />
        </x-slot:icon>
        <x-slot:actions>
            @if (! $this->type)
                <x-pulse::select
                    wire:model.live="usage"
                    id="select-mcp-usage-by"
                    label="Top 10 users"
                    :options="[
                        'calls' => 'calling tools',
                        'slow_calls' => 'experiencing slow tools',
                    ]"
                    class="flex-1"
                    @change="loading = true"
                />
            @endif
        </x-slot:actions>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand" wire:poll.5s="">
        @if ($userToolCallCounts->isEmpty())
            <x-pulse::no-results />
        @else
            <div class="grid grid-cols-1 @lg:grid-cols-2 @3xl:grid-cols-3 @6xl:grid-cols-4 gap-2">
                @foreach ($userToolCallCounts as $userToolCallCount)
                    <x-pulse::user-card wire:key="{{ $userToolCallCount->key }}" :user="$userToolCallCount->user">
                        <x-slot:stats>
                            @if ($sampleRate < 1)
                                <span title="Sample rate: {{ $sampleRate }}, Raw value: {{ number_format($userToolCallCount->count) }}">~{{ number_format($userToolCallCount->count * (1 / $sampleRate)) }}</span>
                            @else
                                {{ number_format($userToolCallCount->count) }}
                            @endif
                        </x-slot:stats>
                    </x-pulse::user-card>
                @endforeach
            </div>
        @endif
    </x-pulse::scroll>
</x-pulse::card>
