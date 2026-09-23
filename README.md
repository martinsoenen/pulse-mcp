# Pulse MCP

Laravel Pulse cards for the tool calls served by a [`laravel/mcp`](https://github.com/laravel/mcp) server.

Pulse already shows you the HTTP request that carried an MCP call, but it aggregates it by route: every tool call
collapses into a single `POST /mcp` row. This package breaks that row apart, per tool.

## What it records

| Type | Key | Value |
| --- | --- | --- |
| `mcp_tool_call` | tool name | — |
| `mcp_tool_error` | tool name | — |
| `mcp_slow_tool_call` | tool name | duration in milliseconds |
| `mcp_user_tool_call` | authenticated user id | — |
| `mcp_slow_user_tool_call` | authenticated user id | — |

A tool call is slow once it reaches the Slow Requests threshold (`PULSE_SLOW_REQUESTS_THRESHOLD`, 1000 ms by default).
Sharing that setting is deliberate: a slow tool is precisely what makes the `POST /mcp` request carrying it slow.

A tool counts as failed when it returns `Response::error()`, when it throws, and when the call itself is rejected
(unknown tool, invalid parameters). The first case is worth stressing: `laravel/mcp` never reports it to the
exception handler, so without this package a failing tool is completely invisible.

## Installation

```bash
composer require martinsoenen/pulse-mcp
```

Then add the cards to your published `resources/views/vendor/pulse/dashboard.blade.php`:

```blade
<livewire:pulse-mcp.usage cols="4" />
<livewire:pulse-mcp.slow-tools cols="4" />
<livewire:pulse-mcp.tool-usage cols="4" />
```

Like core's Application Usage card, the usage card lets you switch between the users calling tools and the users
experiencing slow tools. Pin it to one of them with `type="calls"` or `type="slow_calls"`.

## Configuration

The recorder is enabled by default. To tune it, add it to the `recorders` of your `config/pulse.php`:

```php
\MartinSoenen\PulseMcp\Recorders\McpToolCalls::class => [
    'enabled' => env('PULSE_MCP_ENABLED', true),
    'sample_rate' => env('PULSE_MCP_SAMPLE_RATE', 1),
],
```

Sampling works as in Pulse's own recorders: a call is kept or dropped as a whole, and the cards scale every count
back up, prefixed with `~`, the raw value showing on hover.

## How it works, and what that costs

`laravel/mcp` dispatches no event around tool calls, and `CallTool` builds its `ToolInvoker` with `new`, so there is
no supported way to observe an invocation. What the package does instead is decorate the `tools/call` handler that
`Server::runMethodHandle()` resolves from the container.

That works, but it leans on a class the package does not document as an extension point. Two limits follow:

- **Tools called through `execute_tools` are not recorded.** `ToolSearch::execute()` bypasses `CallTool` and drives
  `ToolInvoker` directly, so those inner calls never reach the decorator.
- **Streamed tools need care.** A tool returning a `Generator` only runs while the framework iterates it, which
  happens after the handler returns. The package wraps the generator so the recorded duration covers the whole
  stream; measuring around the handler alone reports roughly a tenth of the real time.

Both limits disappear if `laravel/mcp` ever emits events around tool invocations.

## Requirements

PHP 8.3+, Laravel 12 or 13, Livewire 3 or 4, `laravel/mcp` 1.x and `laravel/pulse` 1.8+.
