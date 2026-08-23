<?php

use Laravel\Mcp\Server\Testing\TestResponse;
use Laravel\Mcp\Transport\JsonRpcResponse;

/**
 * @return array<string, mixed>
 */
function mcpRpc(TestResponse $response): array
{
    $property = new ReflectionProperty($response, 'response');
    $payload = $property->getValue($response);

    if (! $payload instanceof JsonRpcResponse) {
        return [];
    }

    return $payload->toArray();
}

/**
 * @return list<array<string, mixed>>
 */
function mcpToolContent(TestResponse $response): array
{
    $result = mcpRpc($response)['result'] ?? null;

    if (! is_array($result)) {
        return [];
    }

    $content = $result['content'] ?? null;

    if (! is_array($content)) {
        return [];
    }

    $items = [];

    foreach ($content as $item) {
        if (! is_array($item)) {
            continue;
        }

        $row = [];

        foreach ($item as $key => $value) {
            if (is_string($key)) {
                $row[$key] = $value;
            }
        }

        $items[] = $row;
    }

    return $items;
}

/**
 * @param  array<string, mixed>  $arguments
 * @return array{jsonrpc: string, id: int, method: string, params: array{name: string, arguments: array<string, mixed>}}
 */
function mcpTicketsCall(string $name = 'list_tickets', array $arguments = []): array
{
    return [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => [
            'name' => $name,
            'arguments' => $arguments,
        ],
    ];
}
