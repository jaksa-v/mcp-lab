<?php

use App\Models\Ticket;
use App\Models\User;
use Laravel\Passport\Passport;

use function Pest\Laravel\postJson;
use function Pest\Laravel\withToken;

test('a request without a token is rejected', function () {
    $response = postJson('/mcp/tickets', mcpTicketsCall())
        ->assertUnauthorized();

    expect((string) $response->headers->get('WWW-Authenticate'))
        ->toContain('Bearer realm="mcp"')
        ->toContain('.well-known/oauth-protected-resource/mcp/tickets');
});

test('a request with a garbage token is rejected', function () {
    withToken('not-a-real-token')
        ->postJson('/mcp/tickets', mcpTicketsCall())
        ->assertUnauthorized();
});

test('sam can list tickets with a passport token', function () {
    $sam = User::factory()->agent()->create([
        'email' => 'sam@northwind.test',
        'name' => 'Sam Carter',
    ]);
    Ticket::factory()->open()->create([
        'subject' => 'Warehouse door is jammed',
    ]);

    Passport::actingAs($sam, ['mcp:use']);

    postJson('/mcp/tickets', mcpTicketsCall())
        ->assertOk()
        ->assertJsonPath('result.structuredContent.tickets.0.subject', 'Warehouse door is jammed');
});
