<?php

namespace MartinSoenen\PulseMcp;

use Closure;
use Generator;
use Laravel\Mcp\Server\Contracts\Method;
use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Transport\JsonRpcRequest;
use Laravel\Mcp\Transport\JsonRpcResponse;
use Throwable;

/**
 * Times every MCP tool call by decorating the "tools/call" handler.
 *
 * laravel/mcp dispatches no event around tool calls, so the only way to observe
 * them is to wrap the handler the container resolves in Server::runMethodHandle().
 */
class InstrumentedCallTool implements Method
{
    /**
     * @param  Closure(string, int, bool): void  $record
     */
    public function __construct(
        private Method $handler,
        private Closure $record,
    ) {
    }

    /**
     * Handle the tool call, recording how long it took and whether it failed.
     *
     * @return iterable<JsonRpcResponse>|JsonRpcResponse
     */
    public function handle(JsonRpcRequest $request, ServerContext $context): iterable|JsonRpcResponse
    {
        $tool = $request->name() ?? 'unknown';
        $startedAt = hrtime(true);

        try {
            $response = $this->handler->handle($request, $context);
        } catch (Throwable $exception) {
            ($this->record)($tool, $this->millisecondsSince($startedAt), true);

            throw $exception;
        }

        if ($response instanceof Generator) {
            return $this->recordWhenStreamEnds($response, $tool, $startedAt);
        }

        ($this->record)($tool, $this->millisecondsSince($startedAt), $this->failed($response));

        return $response;
    }

    /**
     * Yield the streamed responses untouched, then record once the stream ends.
     *
     * A streamed tool only runs while the framework iterates the generator, which
     * happens after handle() returns. Recording here instead of above is what
     * keeps the measured duration honest.
     *
     * @param  Generator<JsonRpcResponse>  $responses
     * @return Generator<JsonRpcResponse>
     */
    private function recordWhenStreamEnds(Generator $responses, string $tool, int|float $startedAt): Generator
    {
        $failed = false;

        try {
            foreach ($responses as $response) {
                $failed = $failed || $this->failed($response);

                yield $response;
            }
        } finally {
            ($this->record)($tool, $this->millisecondsSince($startedAt), $failed);
        }
    }

    /**
     * Determine whether the response reports a failure.
     *
     * A tool that throws is turned into an "isError" result by CallTool, while a
     * malformed call surfaces as a JSON-RPC error instead.
     */
    private function failed(JsonRpcResponse $response): bool
    {
        if (array_key_exists('error', $response->content)) {
            return true;
        }

        $result = $response->content['result'] ?? [];

        return is_array($result) && ($result['isError'] ?? false) === true;
    }

    private function millisecondsSince(int|float $startedAt): int
    {
        return (int) round((hrtime(true) - $startedAt) / 1_000_000);
    }
}
