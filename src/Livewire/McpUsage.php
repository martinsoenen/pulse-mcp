<?php

namespace MartinSoenen\PulseMcp\Livewire;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Laravel\Pulse\Facades\Pulse;
use Laravel\Pulse\Livewire\Card;
use Laravel\Pulse\Recorders\SlowRequests;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Url;
use MartinSoenen\PulseMcp\Recorders\McpToolCalls;

#[Lazy]
class McpUsage extends Card
{
    /**
     * The type of usage to show, when the card is pinned to one.
     *
     * @var 'calls'|'slow_calls'|null
     */
    public ?string $type = null;

    /**
     * The usage type picked in the card.
     *
     * Core's Application Usage card already owns the "usage" query parameter.
     *
     * @var 'calls'|'slow_calls'
     */
    #[Url(as: 'mcp-usage')]
    public string $usage = 'calls';

    /**
     * Render the component.
     */
    public function render(): Renderable
    {
        $type = $this->type ?? $this->usage;

        [$userToolCallCounts, $time, $runAt] = $this->remember(
            function () use ($type) {
                $counts = $this->aggregate(
                    match ($type) {
                        'calls' => 'mcp_user_tool_call',
                        'slow_calls' => 'mcp_slow_user_tool_call',
                    },
                    'count',
                    limit: 10,
                );

                $users = Pulse::resolveUsers($counts->pluck('key'));

                return $counts->map(fn ($row) => (object) [
                    'key' => $row->key,
                    'user' => $users->find($row->key),
                    'count' => (int) $row->count,
                ]);
            },
            $type,
        );

        return View::make('pulse-mcp::livewire.usage', [
            'time' => $time,
            'runAt' => $runAt,
            'userToolCallCounts' => $userToolCallCounts,
            'threshold' => Config::get('pulse.recorders.'.SlowRequests::class.'.threshold', 1_000),
            'sampleRate' => Config::get('pulse.recorders.'.McpToolCalls::class.'.sample_rate', 1),
        ]);
    }
}
