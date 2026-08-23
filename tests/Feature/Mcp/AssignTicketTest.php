<?php

use App\Mcp\Servers\TicketsServer;
use App\Mcp\Tools\AssignTicketTool;
use App\Models\Ticket;
use App\Models\User;

test('an agent can assign a ticket by user id', function () {
    $agent = User::factory()->agent()->create();
    $assignee = User::factory()->agent()->create(['name' => 'Sam Agent']);
    $ticket = Ticket::factory()->create();

    TicketsServer::actingAs($agent)
        ->tool(AssignTicketTool::class, [
            'ticket_id' => $ticket->id,
            'assignee_id' => $assignee->id,
        ])
        ->assertOk()
        ->assertSee('Assigned ticket #'.$ticket->id.' to Sam Agent.');

    expect($ticket->fresh()->assignee_id)->toBe($assignee->id);
});

test('assigning the same person twice still succeeds', function () {
    $agent = User::factory()->agent()->create();
    $assignee = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create(['assignee_id' => $assignee->id]);

    TicketsServer::actingAs($agent)
        ->tool(AssignTicketTool::class, [
            'ticket_id' => $ticket->id,
            'assignee_id' => $assignee->id,
        ])
        ->assertOk();

    expect($ticket->fresh()->assignee_id)->toBe($assignee->id);
});

test('a requester cannot assign a ticket', function () {
    $requester = User::factory()->requester()->create();
    $assignee = User::factory()->agent()->create();
    $ticket = Ticket::factory()->for($requester, 'requester')->create();

    TicketsServer::actingAs($requester)
        ->tool(AssignTicketTool::class, [
            'ticket_id' => $ticket->id,
            'assignee_id' => $assignee->id,
        ])
        ->assertHasErrors()
        ->assertSee('Permission denied.');

    expect($ticket->fresh()->assignee_id)->toBeNull();
});

test('a missing assignee is rejected with a sentence', function () {
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create();

    TicketsServer::actingAs($agent)
        ->tool(AssignTicketTool::class, [
            'ticket_id' => $ticket->id,
            'assignee_id' => 9999,
        ])
        ->assertHasErrors()
        ->assertSee('No person matches that assignee id.');
});
