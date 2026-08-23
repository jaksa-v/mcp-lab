<?php

use App\Mcp\Prompts\TriageTicketPrompt;
use App\Mcp\Servers\TicketsServer;
use App\Models\Ticket;
use App\Models\User;

test('triage_ticket returns an assistant message and a user message with the ticket subject', function () {
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create([
        'subject' => 'Chargeback on order 4412',
        'body' => 'Customer emailed legal@northwind.test.',
    ]);

    TicketsServer::actingAs($agent)
        ->prompt(TriageTicketPrompt::class, [
            'ticket_id' => $ticket->id,
        ])
        ->assertOk()
        ->assertName('triage_ticket')
        ->assertSee('You are triaging Northwind Support tickets.')
        ->assertSee('desk://playbook')
        ->assertSee('Triage ticket #'.$ticket->id.': Chargeback on order 4412')
        ->assertSee('Customer emailed legal@northwind.test.');
});

test('a requester cannot triage someone elses ticket', function () {
    $requester = User::factory()->requester()->create();
    $ticket = Ticket::factory()->create();

    TicketsServer::actingAs($requester)
        ->prompt(TriageTicketPrompt::class, [
            'ticket_id' => $ticket->id,
        ])
        ->assertHasErrors()
        ->assertSee('Permission denied.');
});

test('a missing ticket id is rejected with a sentence', function () {
    $agent = User::factory()->agent()->create();

    TicketsServer::actingAs($agent)
        ->prompt(TriageTicketPrompt::class, [])
        ->assertHasErrors()
        ->assertSee('Give me the ticket id to triage, like 12.');
});

test('a missing ticket returns a useful error', function () {
    $agent = User::factory()->agent()->create();

    TicketsServer::actingAs($agent)
        ->prompt(TriageTicketPrompt::class, [
            'ticket_id' => 9999,
        ])
        ->assertHasErrors()
        ->assertSee('No ticket matches that id');
});
