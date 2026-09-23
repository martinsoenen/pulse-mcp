<?php

namespace MartinSoenen\PulseMcp\Tests;

use Illuminate\Foundation\Auth\User;
use Laravel\Pulse\Facades\Pulse;
use Livewire\Livewire;
use MartinSoenen\PulseMcp\Livewire\McpToolUsage;
use MartinSoenen\PulseMcp\Livewire\McpUsage;
use MartinSoenen\PulseMcp\Livewire\SlowMcpTools;
use MartinSoenen\PulseMcp\Recorders\McpToolCalls;
use MartinSoenen\PulseMcp\Tests\Fixtures\FailingTool;
use MartinSoenen\PulseMcp\Tests\Fixtures\FastTool;
use MartinSoenen\PulseMcp\Tests\Fixtures\SluggishTool;
use MartinSoenen\PulseMcp\Tests\Fixtures\StreamingTool;
use MartinSoenen\PulseMcp\Tests\Fixtures\TestServer;

class CardsTest extends TestCase
{
    public function test_the_usage_card_shows_who_called_the_tools(): void
    {
        $user = User::forceCreate(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'secret']);

        TestServer::actingAs($user)->tool(FastTool::class);
        Pulse::ingest();

        Livewire::withoutLazyLoading()->test(McpUsage::class)->assertSee('Ada Lovelace');
    }

    public function test_the_usage_card_can_show_who_experienced_slow_tools(): void
    {
        $ada = User::forceCreate(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'secret']);
        $grace = User::forceCreate(['name' => 'Grace Hopper', 'email' => 'grace@example.com', 'password' => 'secret']);

        TestServer::actingAs($ada)->tool(StreamingTool::class);
        TestServer::actingAs($grace)->tool(FastTool::class);
        Pulse::ingest();

        Livewire::withoutLazyLoading()->test(McpUsage::class)
            ->assertSee(['Ada Lovelace', 'Grace Hopper'])
            ->set('usage', 'slow_calls')
            ->assertSee(['Ada Lovelace', '20ms threshold'])
            ->assertDontSee('Grace Hopper');
    }

    public function test_the_usage_card_can_be_pinned_to_slow_tools(): void
    {
        Livewire::withoutLazyLoading()->test(McpUsage::class, ['type' => 'slow_calls'])
            ->assertSee('Top 10 Users Experiencing Slow MCP Tools')
            ->assertDontSeeHtml('select-mcp-usage-by');
    }

    public function test_the_cards_scale_the_counts_by_the_sample_rate(): void
    {
        $ada = User::forceCreate(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'secret']);

        TestServer::actingAs($ada)->tool(StreamingTool::class);
        Pulse::ingest();

        config()->set('pulse.recorders.'.McpToolCalls::class.'.sample_rate', 0.5);

        foreach ([McpUsage::class, SlowMcpTools::class, McpToolUsage::class] as $card) {
            Livewire::withoutLazyLoading()->test($card)
                ->assertSee('~2')
                ->assertSee('Sample rate: 0.5, Raw value: 1');
        }
    }

    public function test_the_slow_tools_card_only_lists_the_tools_over_the_threshold(): void
    {
        TestServer::tool(FastTool::class);
        TestServer::tool(StreamingTool::class);
        Pulse::ingest();

        Livewire::withoutLazyLoading()->test(SlowMcpTools::class)
            ->assertSee('20ms threshold')
            ->assertSeeHtml('title="streaming"')
            ->assertDontSeeHtml('title="fast"');
    }

    public function test_the_slow_tools_card_sorts_by_slowest_or_by_count(): void
    {
        TestServer::tool(SluggishTool::class);
        TestServer::tool(StreamingTool::class);
        TestServer::tool(StreamingTool::class);
        Pulse::ingest();

        Livewire::withoutLazyLoading()->test(SlowMcpTools::class)
            ->assertSeeInOrder(['sluggish', 'streaming'])
            ->set('orderBy', 'count')
            ->assertSeeInOrder(['streaming', 'sluggish']);
    }

    public function test_the_cards_show_an_empty_state_before_any_call(): void
    {
        foreach ([McpUsage::class, SlowMcpTools::class, McpToolUsage::class] as $card) {
            Livewire::withoutLazyLoading()->test($card)->assertSee('No results');
        }
    }

    public function test_the_tool_usage_card_counts_the_errors(): void
    {
        TestServer::tool(FailingTool::class);
        Pulse::ingest();

        Livewire::withoutLazyLoading()->test(McpToolUsage::class)
            ->assertViewHas('tools', fn ($tools) => $tools->sole()->tool === 'failing' && $tools->sole()->errors === 1);
    }
}
