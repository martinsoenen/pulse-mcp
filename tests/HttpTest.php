<?php

namespace MartinSoenen\PulseMcp\Tests;

use Illuminate\Foundation\Auth\User;
use Illuminate\Testing\TestResponse;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Pulse\Facades\Pulse;
use MartinSoenen\PulseMcp\Tests\Fixtures\TestServer;

/**
 * Laravel's test client terminates the request without sending the response, so
 * a streamed body only runs once the test reads it. Each test therefore ingests
 * explicitly, after the response is fully consumed, as production would.
 */
class HttpTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        Mcp::web('mcp', TestServer::class);
    }

    public function test_it_records_a_tool_called_over_http(): void
    {
        $this->callTool('fast')->assertOk();

        Pulse::ingest();

        $this->assertSame('fast', $this->entries('mcp_tool_call')->sole()->key);
    }

    public function test_it_measures_a_tool_streamed_over_http_until_the_stream_ends(): void
    {
        $this->callTool('streaming')->streamedContent();

        Pulse::ingest();

        $this->assertGreaterThanOrEqual(30, $this->entries('mcp_slow_tool_call')->sole()->value);
    }

    public function test_it_attributes_an_http_call_to_the_authenticated_user(): void
    {
        $user = User::forceCreate(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'secret']);

        $this->actingAs($user)->callTool('fast');

        Pulse::ingest();

        $this->assertSame((string) $user->getKey(), $this->entries('mcp_user_tool_call')->sole()->key);
    }

    public function test_it_records_a_call_to_an_unknown_tool_as_a_failure(): void
    {
        $this->callTool('missing');

        Pulse::ingest();

        $this->assertSame('missing', $this->entries('mcp_tool_error')->sole()->key);
    }

    private function callTool(string $name): TestResponse
    {
        return $this->postJson('mcp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => $name,
                'arguments' => (object) [],
                '_meta' => [
                    'io.modelcontextprotocol/protocolVersion' => '2026-07-28',
                    'io.modelcontextprotocol/clientCapabilities' => (object) [],
                ],
            ],
        ], [
            'Accept' => 'application/json, text/event-stream',
            'MCP-Protocol-Version' => '2026-07-28',
            'Mcp-Method' => 'tools/call',
            'Mcp-Name' => $name,
        ]);
    }
}
