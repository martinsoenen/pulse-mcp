<?php

namespace MartinSoenen\PulseMcp\Tests;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Server\McpServiceProvider;
use Laravel\Pulse\PulseServiceProvider;
use Laravel\Pulse\Recorders\SlowRequests;
use Livewire\LivewireServiceProvider;
use MartinSoenen\PulseMcp\PulseMcpServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            McpServiceProvider::class,
            PulseServiceProvider::class,
            PulseMcpServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('pulse.enabled', true);
        $app['config']->set('pulse.ingest.driver', 'storage');
        $app['config']->set('pulse.recorders.'.SlowRequests::class.'.threshold', 20);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__.'/../vendor/laravel/pulse/database/migrations');
    }

    /**
     * Read the entries Pulse stored for the given type.
     *
     * @return Collection<int, object>
     */
    protected function entries(string $type): Collection
    {
        return DB::table('pulse_entries')->where('type', $type)->get();
    }
}
