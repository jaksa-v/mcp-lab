<?php

use App\Mcp\Servers\TicketsServer;
use App\Mcp\Tools\AssignTicketTool;
use App\Models\Ticket;
use App\Models\User;
use Laravel\Mcp\Client;
use Laravel\Mcp\Facades\Mcp;

test('the named directory client lists tools from this app', function () {
    Mcp::registerClient('directory', fn (): Client => Client::local(PHP_BINARY, [
        base_path('artisan'),
        'mcp:start',
        'directory',
    ]));

    $tools = Mcp::client('directory')->tools();

    expect($tools->has('get_person'))->toBeTrue();
    expect($tools->has('search_people'))->toBeTrue();
    expect($tools->has('list_teams'))->toBeTrue();
    expect($tools->has('who_is_on_call'))->toBeTrue();
});

test('assign fails when the directory client is unplugged', function () {
    $agent = User::factory()->agent()->create();
    $assignee = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create();

    Mcp::registerClient('directory', fn (): Client => Client::local(PHP_BINARY, [
        '-r',
        'exit(1);',
    ]));

    TicketsServer::actingAs($agent)
        ->tool(AssignTicketTool::class, [
            'ticket_id' => $ticket->id,
            'assignee_id' => $assignee->id,
        ])
        ->assertHasErrors()
        ->assertSee('No person matches that assignee id.');

    expect($ticket->fresh()->assignee_id)->toBeNull();
});
