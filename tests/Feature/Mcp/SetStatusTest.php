<?php

use App\Enums\Status;
use App\Mcp\Servers\TicketsServer;
use App\Mcp\Tools\SetStatusTool;
use App\Models\Ticket;
use App\Models\User;

test('an agent can change ticket status', function () {
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->open()->create();

    TicketsServer::actingAs($agent)
        ->tool(SetStatusTool::class, [
            'ticket_id' => $ticket->id,
            'status' => Status::Pending->value,
        ])
        ->assertOk()
        ->assertSee('Ticket #'.$ticket->id.' is now pending.');

    expect($ticket->fresh()->status)->toBe(Status::Pending);
});

test('a requester cannot change status', function () {
    $requester = User::factory()->requester()->create();
    $ticket = Ticket::factory()->for($requester, 'requester')->open()->create();

    TicketsServer::actingAs($requester)
        ->tool(SetStatusTool::class, [
            'ticket_id' => $ticket->id,
            'status' => Status::Closed->value,
        ])
        ->assertHasErrors()
        ->assertSee('Permission denied.');

    expect($ticket->fresh()->status)->toBe(Status::Open);
});

test('an invalid status is rejected with a sentence', function () {
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create();

    TicketsServer::actingAs($agent)
        ->tool(SetStatusTool::class, [
            'ticket_id' => $ticket->id,
            'status' => 'exploded',
        ])
        ->assertHasErrors()
        ->assertSee('Status must be open, pending, or closed.');
});
