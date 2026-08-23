<?php

use App\Mcp\Prompts\DraftReplyPrompt;
use App\Mcp\Servers\TicketsServer;
use App\Models\Ticket;
use App\Models\User;

test('draft_reply returns an assistant message and a user message with the ticket subject', function () {
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create([
        'subject' => 'Chargeback on order 4412',
        'body' => 'Customer emailed legal@northwind.test.',
    ]);

    TicketsServer::actingAs($agent)
        ->prompt(DraftReplyPrompt::class, [
            'ticket_id' => $ticket->id,
            'tone' => 'curt',
        ])
        ->assertOk()
        ->assertName('draft_reply')
        ->assertSee('You are a Northwind Support agent writing a customer-facing reply.')
        ->assertSee('Write in a curt tone.')
        ->assertSee('Draft a reply to ticket #'.$ticket->id.': Chargeback on order 4412')
        ->assertSee('Customer emailed legal@northwind.test.');
});

test('a requester cannot draft a reply to someone elses ticket', function () {
    $requester = User::factory()->requester()->create();
    $ticket = Ticket::factory()->create([
        'subject' => 'Internal warehouse note',
    ]);

    TicketsServer::actingAs($requester)
        ->prompt(DraftReplyPrompt::class, [
            'ticket_id' => $ticket->id,
            'tone' => 'warm',
        ])
        ->assertHasErrors()
        ->assertSee('Permission denied.');
});

test('a requester can draft a reply to their own ticket', function () {
    $requester = User::factory()->requester()->create();
    $ticket = Ticket::factory()->for($requester, 'requester')->create([
        'subject' => 'Where is my pallet',
    ]);

    TicketsServer::actingAs($requester)
        ->prompt(DraftReplyPrompt::class, [
            'ticket_id' => $ticket->id,
            'tone' => 'warm',
        ])
        ->assertOk()
        ->assertSee('Where is my pallet');
});

test('a missing tone is rejected with a sentence', function () {
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create();

    TicketsServer::actingAs($agent)
        ->prompt(DraftReplyPrompt::class, [
            'ticket_id' => $ticket->id,
        ])
        ->assertHasErrors()
        ->assertSee('Say how the reply should sound, like curt, warm, or formal.');
});

test('a missing ticket returns a useful error', function () {
    $agent = User::factory()->agent()->create();

    TicketsServer::actingAs($agent)
        ->prompt(DraftReplyPrompt::class, [
            'ticket_id' => 9999,
            'tone' => 'curt',
        ])
        ->assertHasErrors()
        ->assertSee('No ticket matches that id');
});
