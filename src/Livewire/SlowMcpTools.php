<?php

namespace MartinSoenen\PulseMcp\Livewire;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Laravel\Pulse\Livewire\Card;
use Laravel\Pulse\Recorders\SlowRequests;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Url;
use MartinSoenen\PulseMcp\Recorders\McpToolCalls;

#[Lazy]
class SlowMcpTools extends Card
{
    /**
     * The column to sort by.
     *
     * @var 'slowest'|'count'
     */
    #[Url(as: 'slow-mcp-tools')]
    public string $orderBy = 'slowest';

    /**
     * Render the component.
     */
    public function render(): Renderable
    {
        [$tools, $time, $runAt] = $this->remember(
            fn () => $this->aggregate(
                'mcp_slow_tool_call',
                ['max', 'count'],
                match ($this->orderBy) {
                    'count' => 'count',
                    default => 'max',
                },
            )->map(fn ($row) => (object) [
                'tool' => $row->key,
                'count' => (int) $row->count,
                'slowest' => (int) $row->max,
            ]),
            $this->orderBy,
        );

        return View::make('pulse-mcp::livewire.slow-tools', [
            'time' => $time,
            'runAt' => $runAt,
            'tools' => $tools,
            'threshold' => Config::get('pulse.recorders.'.SlowRequests::class.'.threshold', 1_000),
            'sampleRate' => Config::get('pulse.recorders.'.McpToolCalls::class.'.sample_rate', 1),
        ]);
    }
}
