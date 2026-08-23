<?php

use App\Models\User;
use Laravel\Passport\Passport;

use function Pest\Laravel\postJson;
use function Pest\Laravel\withToken;

test('a request without a token is rejected', function () {
    $response = postJson('/mcp/directory', mcpTicketsCall('search_people', ['query' => 'Sam']))
        ->assertUnauthorized();

    expect((string) $response->headers->get('WWW-Authenticate'))
        ->toContain('Bearer realm="mcp"')
        ->toContain('.well-known/oauth-protected-resource/mcp/directory');
});

test('a request with a garbage token is rejected', function () {
    withToken('not-a-real-token')
        ->postJson('/mcp/directory', mcpTicketsCall('search_people', ['query' => 'Sam']))
        ->assertUnauthorized();
});

test('sam can search people with a passport token', function () {
    $sam = User::factory()->agent()->create([
        'email' => 'sam@northwind.test',
        'name' => 'Sam Carter',
    ]);

    Passport::actingAs($sam, ['mcp:use']);

    postJson('/mcp/directory', mcpTicketsCall('search_people', ['query' => 'Sam']))
        ->assertOk()
        ->assertJsonPath('result.structuredContent.people.0.name', 'Sam Carter');
});
