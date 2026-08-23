<?php

use App\Models\Ticket;
use App\Models\User;

use function Pest\Laravel\postJson;
use function Pest\Laravel\withToken;

test('a request without a token is rejected', function () {
    postJson('/mcp/tickets', mcpTicketsCall())
        ->assertUnauthorized()
        ->assertHeader('WWW-Authenticate', 'Bearer realm="mcp", error="invalid_token"');
});

test('a request with a garbage token is rejected', function () {
    withToken('not-a-real-token')
        ->postJson('/mcp/tickets', mcpTicketsCall())
        ->assertUnauthorized();
});

test('sam can list tickets with a personal access token', function () {
    $sam = User::factory()->agent()->create([
        'email' => 'sam@northwind.test',
        'name' => 'Sam Carter',
    ]);
    Ticket::factory()->open()->create([
        'subject' => 'Warehouse door is jammed',
    ]);

    withToken($sam->createToken('inspector')->plainTextToken)
        ->postJson('/mcp/tickets', mcpTicketsCall())
        ->assertOk()
        ->assertJsonPath('result.structuredContent.tickets.0.subject', 'Warehouse door is jammed');
});
