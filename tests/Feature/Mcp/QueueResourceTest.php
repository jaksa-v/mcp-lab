<?php

use App\Mcp\Resources\QueueResource;
use App\Mcp\Servers\TicketsServer;
use App\Models\Ticket;
use App\Models\User;

test('an agent sees open tickets on the queue', function () {
    $agent = User::factory()->agent()->create();
    $open = Ticket::factory()->open()->create([
        'subject' => 'Pallet stuck at dock 4',
    ]);
    Ticket::factory()->closed()->create([
        'subject' => 'Already shipped last week',
    ]);

    TicketsServer::actingAs($agent)
        ->resource(QueueResource::class)
        ->assertOk()
        ->assertSee('Open queue')
        ->assertSee('#'.$open->id)
        ->assertSee('Pallet stuck at dock 4')
        ->assertDontSee('Already shipped last week');
});

test('a requester only sees their own open tickets on the queue', function () {
    $requester = User::factory()->requester()->create();
    Ticket::factory()->for($requester, 'requester')->open()->create([
        'subject' => 'My refund is late',
    ]);
    Ticket::factory()->open()->create([
        'subject' => 'Someone elses printer',
    ]);

    TicketsServer::actingAs($requester)
        ->resource(QueueResource::class)
        ->assertOk()
        ->assertSee('My refund is late')
        ->assertDontSee('Someone elses printer');
});

test('an empty queue says so', function () {
    $agent = User::factory()->agent()->create();

    TicketsServer::actingAs($agent)
        ->resource(QueueResource::class)
        ->assertOk()
        ->assertSee('No open tickets.');
});
