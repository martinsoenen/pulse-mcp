<?php

namespace MartinSoenen\PulseMcp;

use Illuminate\Support\ServiceProvider;
use Livewire\LivewireManager;
use MartinSoenen\PulseMcp\Livewire\McpToolUsage;
use MartinSoenen\PulseMcp\Livewire\McpUsage;
use MartinSoenen\PulseMcp\Livewire\SlowMcpTools;
use MartinSoenen\PulseMcp\Recorders\McpToolCalls;

class PulseMcpServiceProvider extends ServiceProvider
{
    /**
     * Give the recorder a default configuration.
     *
     * Pulse reads its recorders during boot(), so registering the default here is
     * early enough. mergeConfigFrom() would not work: it is a flat array merge, so
     * a published config/pulse.php replaces the whole "recorders" key.
     */
    public function register(): void
    {
        $config = $this->app->make('config');
        $key = 'pulse.recorders.'.McpToolCalls::class;

        if ($config->get($key) === null) {
            $config->set($key, ['enabled' => true]);
        }
    }

    /**
     * Register the cards.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'pulse-mcp');

        $this->callAfterResolving('livewire', function (LivewireManager $livewire): void {
            $livewire->component('pulse-mcp.usage', McpUsage::class);
            $livewire->component('pulse-mcp.slow-tools', SlowMcpTools::class);
            $livewire->component('pulse-mcp.tool-usage', McpToolUsage::class);
        });

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/pulse-mcp'),
            ], 'pulse-mcp-views');
        }
    }
}
