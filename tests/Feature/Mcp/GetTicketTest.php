<?php

use App\Mcp\Servers\TicketsServer;
use App\Mcp\Tools\GetTicketTool;
use App\Models\Ticket;
use App\Models\User;

test('an agent can read a ticket dossier', function () {
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create([
        'subject' => 'Chargeback on order 4412',
        'body' => 'Customer emailed legal@northwind.test.',
    ]);

    TicketsServer::actingAs($agent)
        ->tool(GetTicketTool::class, ['ticket_id' => $ticket->id])
        ->assertOk()
        ->assertSee('Ticket #'.$ticket->id)
        ->assertSee('Chargeback on order 4412')
        ->assertSee('Customer emailed legal@northwind.test.')
        ->assertSee($ticket->requester->name);
});

test('a requester cannot read someone elses ticket', function () {
    $requester = User::factory()->requester()->create();
    $ticket = Ticket::factory()->create([
        'subject' => 'Internal warehouse note',
    ]);

    TicketsServer::actingAs($requester)
        ->tool(GetTicketTool::class, ['ticket_id' => $ticket->id])
        ->assertHasErrors()
        ->assertSee('Permission denied.');
});

test('a requester can read their own ticket', function () {
    $requester = User::factory()->requester()->create();
    $ticket = Ticket::factory()->for($requester, 'requester')->create([
        'subject' => 'Where is my pallet',
    ]);

    TicketsServer::actingAs($requester)
        ->tool(GetTicketTool::class, ['ticket_id' => $ticket->id])
        ->assertOk()
        ->assertSee('Where is my pallet');
});

test('a missing ticket returns a useful error', function () {
    $agent = User::factory()->agent()->create();

    TicketsServer::actingAs($agent)
        ->tool(GetTicketTool::class, ['ticket_id' => 9999])
        ->assertHasErrors()
        ->assertSee('No ticket matches that id');
});

test('get_ticket marks the response as coming from eloquent', function () {
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create();

    $response = TicketsServer::actingAs($agent)
        ->tool(GetTicketTool::class, ['ticket_id' => $ticket->id])
        ->assertOk();

    $first = mcpToolContent($response)[0] ?? [];
    $meta = $first['_meta'] ?? null;

    expect(is_array($meta) ? $meta : [])->toMatchArray(['source' => 'eloquent']);
});
