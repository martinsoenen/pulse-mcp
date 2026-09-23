<?php

namespace MartinSoenen\PulseMcp\Recorders;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Foundation\Application;
use Laravel\Mcp\Server\Methods\CallTool;
use Laravel\Pulse\Pulse;
use Laravel\Pulse\Recorders\Concerns\Sampling;
use Laravel\Pulse\Recorders\Concerns\Thresholds;
use Laravel\Pulse\Recorders\SlowRequests;
use MartinSoenen\PulseMcp\InstrumentedCallTool;

/**
 * Records every MCP tool call: which tool ran, whether it failed, whether it was
 * slow, and who ran it.
 */
class McpToolCalls
{
    use Sampling, Thresholds;

    public function __construct(
        protected Pulse $pulse,
    ) {
    }

    /**
     * Register the recorder.
     */
    public function register(callable $record, Application $app): void
    {
        $app->extend(CallTool::class, fn (CallTool $handler): InstrumentedCallTool => new InstrumentedCallTool(
            $handler,
            fn (string $tool, int $duration, bool $failed) => $record($tool, $duration, $failed),
        ));
    }

    /**
     * Record the tool call.
     *
     * A tool counts as slow past the Slow Requests threshold: a slow tool is what
     * makes the "POST /mcp" request carrying it slow.
     */
    public function record(string $tool, int $duration, bool $failed): void
    {
        $timestamp = CarbonImmutable::now()->getTimestamp();
        $userId = $this->pulse->resolveAuthenticatedUserId();

        $this->pulse->lazy(function () use ($tool, $duration, $failed, $timestamp, $userId): void {
            if (! $this->shouldSample()) {
                return;
            }

            $isSlow = $duration >= $this->threshold($tool, SlowRequests::class);

            $this->pulse->record('mcp_tool_call', $tool, timestamp: $timestamp)->count();

            if ($failed) {
                $this->pulse->record('mcp_tool_error', $tool, timestamp: $timestamp)->count();
            }

            if ($isSlow) {
                $this->pulse->record('mcp_slow_tool_call', $tool, $duration, $timestamp)->max()->count();
            }

            if ($userId === null) {
                return;
            }

            $this->pulse->record('mcp_user_tool_call', (string) $userId, timestamp: $timestamp)->count();

            if ($isSlow) {
                $this->pulse->record('mcp_slow_user_tool_call', (string) $userId, timestamp: $timestamp)->count();
            }
        });
    }
}
