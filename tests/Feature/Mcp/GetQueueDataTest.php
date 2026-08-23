<?php

use App\Mcp\Servers\TicketsServer;
use App\Mcp\Tools\GetQueueDataTool;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Mcp\Server\Transport\FakeTransporter;

test('an agent can load open tickets for the queue app', function () {
    $agent = User::factory()->agent()->create();
    $open = Ticket::factory()->open()->create([
        'subject' => 'Pallet stuck at dock 4',
    ]);
    Ticket::factory()->closed()->create([
        'subject' => 'Already shipped last week',
    ]);

    TicketsServer::actingAs($agent)
        ->tool(GetQueueDataTool::class)
        ->assertOk()
        ->assertSee('Pallet stuck at dock 4')
        ->assertDontSee('Already shipped last week')
        ->assertStructuredContent(function (AssertableJson $json) use ($open): void {
            $json->has('tickets', 1)
                ->where('tickets.0.id', $open->id)
                ->where('tickets.0.subject', 'Pallet stuck at dock 4')
                ->where('tickets.0.status', 'open')
                ->where('tickets.0.assignee', null);
        });
});

test('a requester only sees their own open tickets in queue data', function () {
    $requester = User::factory()->requester()->create();
    Ticket::factory()->for($requester, 'requester')->open()->create([
        'subject' => 'My refund is late',
    ]);
    Ticket::factory()->open()->create([
        'subject' => 'Someone elses printer',
    ]);

    TicketsServer::actingAs($requester)
        ->tool(GetQueueDataTool::class)
        ->assertOk()
        ->assertSee('My refund is late')
        ->assertDontSee('Someone elses printer');
});

test('an empty queue returns no ticket rows', function () {
    $agent = User::factory()->agent()->create();

    TicketsServer::actingAs($agent)
        ->tool(GetQueueDataTool::class)
        ->assertOk()
        ->assertStructuredContent(function (AssertableJson $json): void {
            $json->has('tickets', 0);
        });
});

test('get_queue_data is app-only so the model does not get a second list tool', function () {
    $agent = User::factory()->agent()->create();

    TicketsServer::actingAs($agent);

    $tool = (new TicketsServer(new FakeTransporter))
        ->createContext()
        ->tools()
        ->sole(fn ($candidate) => $candidate->name() === 'get_queue_data');

    $meta = $tool->toArray()['_meta']['ui'] ?? [];

    expect($meta['visibility'])->toBe(['app']);
});
