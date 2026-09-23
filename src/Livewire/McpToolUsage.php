<?php

namespace MartinSoenen\PulseMcp\Livewire;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;
use MartinSoenen\PulseMcp\Recorders\McpToolCalls;

#[Lazy]
class McpToolUsage extends Card
{
    /**
     * Render the component.
     */
    public function render(): Renderable
    {
        [$tools, $time, $runAt] = $this->remember(
            fn () => $this->aggregateTypes(['mcp_tool_call', 'mcp_tool_error'], 'count')
                ->map(fn ($row) => (object) [
                    'tool' => $row->key,
                    'calls' => (int) ($row->mcp_tool_call ?? 0),
                    'errors' => (int) ($row->mcp_tool_error ?? 0),
                ]),
        );

        return View::make('pulse-mcp::livewire.tool-usage', [
            'time' => $time,
            'runAt' => $runAt,
            'tools' => $tools,
            'sampleRate' => Config::get('pulse.recorders.'.McpToolCalls::class.'.sample_rate', 1),
        ]);
    }
}
