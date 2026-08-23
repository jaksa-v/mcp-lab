<?php

use App\Mcp\Servers\TicketsServer;
use App\Mcp\Tools\DeleteTicketTool;
use App\Models\Ticket;
use App\Models\User;
use Laravel\Mcp\Server\Transport\FakeTransporter;

test('an agent does not see delete_ticket in the tool list', function () {
    $agent = User::factory()->agent()->create();

    TicketsServer::actingAs($agent);

    $names = (new TicketsServer(new FakeTransporter))
        ->createContext()
        ->tools()
        ->map(fn ($tool) => $tool->name())
        ->all();

    expect($names)->toContain('list_tickets');
    expect($names)->not->toContain('delete_ticket');
});

test('an admin sees delete_ticket in the tool list', function () {
    $admin = User::factory()->admin()->create();

    TicketsServer::actingAs($admin);

    $names = (new TicketsServer(new FakeTransporter))
        ->createContext()
        ->tools()
        ->map(fn ($tool) => $tool->name())
        ->all();

    expect($names)->toContain('delete_ticket');
});

test('an admin can delete a ticket', function () {
    $admin = User::factory()->admin()->create();
    $ticket = Ticket::factory()->create();

    TicketsServer::actingAs($admin)
        ->tool(DeleteTicketTool::class, ['ticket_id' => $ticket->id])
        ->assertOk()
        ->assertSee('Deleted ticket #'.$ticket->id);

    expect(Ticket::query()->find($ticket->id))->toBeNull();
});

test('an agent cannot invoke delete_ticket', function () {
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create();

    TicketsServer::actingAs($agent)
        ->tool(DeleteTicketTool::class, ['ticket_id' => $ticket->id])
        ->assertHasErrors()
        ->assertSee('Tool [delete_ticket] not found.');

    expect(Ticket::query()->find($ticket->id))->not->toBeNull();
});

test('a missing ticket returns a useful error', function () {
    $admin = User::factory()->admin()->create();

    TicketsServer::actingAs($admin)
        ->tool(DeleteTicketTool::class, ['ticket_id' => 9999])
        ->assertHasErrors()
        ->assertSee('No ticket matches that id');
});
