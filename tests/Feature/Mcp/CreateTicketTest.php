<?php

use App\Enums\Priority;
use App\Enums\Status;
use App\Mcp\Servers\TicketsServer;
use App\Mcp\Tools\CreateTicketTool;
use App\Models\Ticket;
use App\Models\User;
use Laravel\Mcp\Server\Transport\FakeTransporter;

test('a requester can open a ticket and cannot delete it', function () {
    $requester = User::factory()->requester()->create();

    TicketsServer::actingAs($requester)
        ->tool(CreateTicketTool::class, [
            'subject' => 'Where is my pallet',
            'body' => 'Dock 4 never scanned the inbound.',
        ])
        ->assertOk()
        ->assertSee('Opened ticket #')
        ->assertSee('Where is my pallet');

    $ticket = Ticket::query()->where('subject', 'Where is my pallet')->sole();

    expect($ticket->requester_id)->toBe($requester->id)
        ->and($ticket->status)->toBe(Status::Open)
        ->and($ticket->priority)->toBe(Priority::Normal)
        ->and($ticket->body)->toBe('Dock 4 never scanned the inbound.')
        ->and($requester->can('delete', $ticket))->toBeFalse();
});

test('create_ticket stores the given priority', function () {
    $requester = User::factory()->requester()->create();

    TicketsServer::actingAs($requester)
        ->tool(CreateTicketTool::class, [
            'subject' => 'Chargeback on order 4412',
            'body' => 'Customer emailed legal@northwind.test.',
            'priority' => Priority::Urgent->value,
        ])
        ->assertOk();

    $ticket = Ticket::query()->where('subject', 'Chargeback on order 4412')->sole();

    expect($ticket->priority)->toBe(Priority::Urgent);
});

test('a missing subject is rejected with a sentence', function () {
    $requester = User::factory()->requester()->create();

    TicketsServer::actingAs($requester)
        ->tool(CreateTicketTool::class, [
            'body' => 'Something is wrong.',
        ])
        ->assertHasErrors()
        ->assertSee('Give the ticket a subject, like "Refund for order 4412".');
});

test('create_ticket advertises version and author metadata', function () {
    $requester = User::factory()->requester()->create();

    TicketsServer::actingAs($requester);

    $tool = (new TicketsServer(new FakeTransporter))
        ->createContext()
        ->tools()
        ->sole(fn ($candidate) => $candidate->name() === 'create_ticket');

    expect($tool->toArray()['_meta'] ?? [])->toMatchArray([
        'version' => '1.0',
        'author' => 'Northwind Support',
    ]);
});
