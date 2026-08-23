<?php

use App\Enums\Status;
use App\Mcp\Servers\TicketsServer;
use App\Mcp\Tools\ListTicketsTool;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Mcp\Server\Transport\FakeTransporter;

test('an agent can list tickets from the database', function () {
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->open()->create([
        'subject' => 'Warehouse door is jammed',
    ]);

    TicketsServer::actingAs($agent)
        ->tool(ListTicketsTool::class)
        ->assertOk()
        ->assertSee((string) $ticket->id)
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
        ->assertSee((string) $own->id)
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

test('list_tickets returns structured ticket rows', function () {
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->open()->create([
        'subject' => 'Warehouse door is jammed',
    ]);

    TicketsServer::actingAs($agent)
        ->tool(ListTicketsTool::class)
        ->assertOk()
        ->assertStructuredContent(function (AssertableJson $json) use ($ticket): void {
            $json->has('tickets', 1)
                ->where('tickets.0.id', $ticket->id)
                ->where('tickets.0.subject', 'Warehouse door is jammed')
                ->where('tickets.0.status', 'open')
                ->where('tickets.0.uri', 'desk://tickets/'.$ticket->id);
        });
});

test('list_tickets advertises an output schema', function () {
    $agent = User::factory()->agent()->create();

    TicketsServer::actingAs($agent);

    $tool = (new TicketsServer(new FakeTransporter))
        ->createContext()
        ->tools()
        ->sole(fn ($candidate) => $candidate->name() === 'list_tickets');

    expect($tool->toArray()['outputSchema']['properties'] ?? [])->toHaveKey('tickets');
});

test('list_tickets includes a resource link for each ticket', function () {
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->open()->create([
        'subject' => 'Warehouse door is jammed',
    ]);

    $response = TicketsServer::actingAs($agent)
        ->tool(ListTicketsTool::class)
        ->assertOk();

    $links = array_values(array_filter(
        mcpToolContent($response),
        fn (array $item): bool => ($item['type'] ?? null) === 'resource_link',
    ));

    expect($links)->toHaveCount(1);
    expect($links[0]['uri'])->toBe('desk://tickets/'.$ticket->id);
    expect($links[0]['name'])->toBe('ticket-'.$ticket->id);
    expect($links[0]['mimeType'])->toBe('text/markdown');
    expect($links[0]['title'])->toBe('Warehouse door is jammed');
});
