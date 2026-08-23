<?php

use App\Enums\Status;
use App\Mcp\Servers\TicketsServer;
use App\Mcp\Tools\ListTicketsTool;
use App\Models\Ticket;
use App\Models\User;

test('an agent can list tickets from the database', function () {
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->open()->create([
        'subject' => 'Warehouse door is jammed',
    ]);

    TicketsServer::actingAs($agent)
        ->tool(ListTicketsTool::class)
        ->assertOk()
        ->assertSee('#'.$ticket->id)
        ->assertSee('Warehouse door is jammed')
        ->assertSee('open');
});

test('a requester only sees their own tickets', function () {
    $requester = User::factory()->requester()->create();
    $own = Ticket::factory()->for($requester, 'requester')->create([
        'subject' => 'My refund is late',
    ]);
    Ticket::factory()->create([
        'subject' => 'Someone elses printer',
    ]);

    TicketsServer::actingAs($requester)
        ->tool(ListTicketsTool::class)
        ->assertOk()
        ->assertSee('My refund is late')
        ->assertSee('#'.$own->id)
        ->assertDontSee('Someone elses printer');
});

test('status filter only returns matching tickets', function () {
    $agent = User::factory()->agent()->create();
    Ticket::factory()->open()->create(['subject' => 'Still open']);
    Ticket::factory()->closed()->create(['subject' => 'Already closed']);

    TicketsServer::actingAs($agent)
        ->tool(ListTicketsTool::class, ['status' => Status::Open->value])
        ->assertOk()
        ->assertSee('Still open')
        ->assertDontSee('Already closed');
});

test('an invalid status is rejected with a sentence', function () {
    $agent = User::factory()->agent()->create();

    TicketsServer::actingAs($agent)
        ->tool(ListTicketsTool::class, ['status' => 'exploded'])
        ->assertHasErrors()
        ->assertSee('Status must be open, pending, or closed.');
});
