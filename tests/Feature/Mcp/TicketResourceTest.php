<?php

use App\Mcp\Resources\TicketResource;
use App\Mcp\Servers\TicketsServer;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use Laravel\Mcp\Server\Transport\FakeTransporter;

test('tickets are listed as a uri template', function () {
    $server = new TicketsServer(new FakeTransporter);
    $templates = $server->createContext()
        ->resourceTemplates()
        ->map(fn ($resource) => (string) $resource->uriTemplate())
        ->all();

    expect($templates)->toContain('desk://tickets/{id}');
});

test('an agent can read a ticket dossier by uri', function () {
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create([
        'subject' => 'Chargeback on order 4412',
        'body' => 'Customer emailed legal@northwind.test.',
    ]);
    Comment::factory()->for($ticket)->for($agent, 'user')->create([
        'body' => 'Asked finance for the original invoice.',
    ]);

    TicketsServer::actingAs($agent)
        ->resource(TicketResource::class, ['id' => $ticket->id])
        ->assertOk()
        ->assertSee('# Ticket #'.$ticket->id.': Chargeback on order 4412')
        ->assertSee('Customer emailed legal@northwind.test.')
        ->assertSee($ticket->requester->name)
        ->assertSee('Asked finance for the original invoice.')
        ->assertSee($agent->name);
});

test('a requester cannot read someone elses ticket dossier', function () {
    $requester = User::factory()->requester()->create();
    $ticket = Ticket::factory()->create([
        'subject' => 'Internal warehouse note',
    ]);

    TicketsServer::actingAs($requester)
        ->resource(TicketResource::class, ['id' => $ticket->id])
        ->assertHasErrors()
        ->assertSee('Permission denied.');
});

test('a requester can read their own ticket dossier', function () {
    $requester = User::factory()->requester()->create();
    $ticket = Ticket::factory()->for($requester, 'requester')->create([
        'subject' => 'Where is my pallet',
    ]);

    TicketsServer::actingAs($requester)
        ->resource(TicketResource::class, ['id' => $ticket->id])
        ->assertOk()
        ->assertSee('Where is my pallet');
});

test('a missing ticket resource returns a useful error', function () {
    $agent = User::factory()->agent()->create();

    TicketsServer::actingAs($agent)
        ->resource(TicketResource::class, ['id' => 9999])
        ->assertHasErrors()
        ->assertSee('No ticket matches that id');
});
