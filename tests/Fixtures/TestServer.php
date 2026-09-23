<?php

namespace MartinSoenen\PulseMcp\Tests\Fixtures;

use Generator;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use RuntimeException;

#[Name('fast')]
class FastTool extends Tool
{
    public function handle(Request $request): Response
    {
        return Response::text('done');
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}

#[Name('failing')]
class FailingTool extends Tool
{
    public function handle(Request $request): Response
    {
        return Response::error('This tool refused to run.');
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}

#[Name('throwing')]
class ThrowingTool extends Tool
{
    public function handle(Request $request): Response
    {
        throw new RuntimeException('Something broke.');
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}

#[Name('sluggish')]
class SluggishTool extends Tool
{
    public function handle(Request $request): Response
    {
        usleep(60_000);

        return Response::text('done, eventually');
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}

#[Name('streaming')]
class StreamingTool extends Tool
{
    /**
     * @return Generator<Response>
     */
    public function handle(Request $request): Generator
    {
        yield Response::text('first');

        usleep(30_000);

        yield Response::text('second');
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}

class TestServer extends Server
{
    protected array $tools = [
        FastTool::class,
        FailingTool::class,
        ThrowingTool::class,
        StreamingTool::class,
        SluggishTool::class,
    ];
}
