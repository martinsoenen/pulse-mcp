<?php

namespace MartinSoenen\PulseMcp\Tests;

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\DB;
use Laravel\Pulse\Facades\Pulse;
use Laravel\Pulse\Recorders\SlowRequests;
use MartinSoenen\PulseMcp\Recorders\McpToolCalls;
use MartinSoenen\PulseMcp\Tests\Fixtures\FailingTool;
use MartinSoenen\PulseMcp\Tests\Fixtures\FastTool;
use MartinSoenen\PulseMcp\Tests\Fixtures\StreamingTool;
use MartinSoenen\PulseMcp\Tests\Fixtures\TestServer;
use MartinSoenen\PulseMcp\Tests\Fixtures\ThrowingTool;
use Orchestra\Testbench\Attributes\DefineEnvironment;

class McpToolCallsTest extends TestCase
{
    public function test_it_records_a_successful_tool_call(): void
    {
        TestServer::tool(FastTool::class)->assertOk();

        Pulse::ingest();

        $this->assertSame('fast', $this->entries('mcp_tool_call')->sole()->key);
        $this->assertCount(0, $this->entries('mcp_tool_error'));
        $this->assertCount(0, $this->entries('mcp_slow_tool_call'));
    }

    public function test_it_records_a_tool_returning_an_error_response(): void
    {
        TestServer::tool(FailingTool::class);

        Pulse::ingest();

        $this->assertSame('failing', $this->entries('mcp_tool_call')->sole()->key);
        $this->assertSame('failing', $this->entries('mcp_tool_error')->sole()->key);
    }

    public function test_it_records_a_tool_that_throws(): void
    {
        TestServer::tool(ThrowingTool::class);

        Pulse::ingest();

        $this->assertSame('throwing', $this->entries('mcp_tool_error')->sole()->key);
    }

    public function test_it_measures_a_streamed_tool_until_the_stream_ends(): void
    {
        TestServer::tool(StreamingTool::class);

        Pulse::ingest();

        $entry = $this->entries('mcp_slow_tool_call')->sole();

        $this->assertSame('streaming', $entry->key);
        $this->assertGreaterThanOrEqual(30, $entry->value);
    }

    public function test_it_uses_the_slow_requests_threshold(): void
    {
        config()->set('pulse.recorders.'.SlowRequests::class.'.threshold', 1_000);

        TestServer::tool(StreamingTool::class);

        Pulse::ingest();

        $this->assertCount(1, $this->entries('mcp_tool_call'));
        $this->assertCount(0, $this->entries('mcp_slow_tool_call'));
    }

    public function test_it_attributes_the_call_to_the_authenticated_user(): void
    {
        TestServer::actingAs(new GenericUser(['id' => 42]))->tool(FastTool::class);

        Pulse::ingest();

        $this->assertSame('42', $this->entries('mcp_user_tool_call')->sole()->key);
    }

    public function test_it_records_slow_calls_against_the_user_who_waited(): void
    {
        TestServer::actingAs(new GenericUser(['id' => 42]))->tool(FastTool::class);
        TestServer::actingAs(new GenericUser(['id' => 42]))->tool(StreamingTool::class);

        Pulse::ingest();

        $this->assertCount(2, $this->entries('mcp_user_tool_call'));
        $this->assertSame('42', $this->entries('mcp_slow_user_tool_call')->sole()->key);
    }

    public function test_it_only_keeps_the_sampled_calls(): void
    {
        config()->set('pulse.recorders.'.McpToolCalls::class.'.sample_rate', 0);

        TestServer::tool(StreamingTool::class)->assertOk();

        Pulse::ingest();

        $this->assertSame(0, DB::table('pulse_entries')->where('type', 'like', 'mcp_%')->count());
    }

    #[DefineEnvironment('disableTheRecorder')]
    public function test_it_records_nothing_once_disabled_in_the_pulse_config(): void
    {
        TestServer::tool(FastTool::class)->assertOk();

        Pulse::ingest();

        $this->assertCount(0, $this->entries('mcp_tool_call'));
    }

    protected function disableTheRecorder($app): void
    {
        $app['config']->set('pulse.recorders.'.McpToolCalls::class, false);
    }
}
