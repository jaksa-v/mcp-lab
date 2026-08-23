<?php

use App\Enums\Status;
use App\Mcp\Servers\TicketsServer;
use App\Mcp\Tools\CloseStaleTicketsTool;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Carbon;

test('an agent can close stale open tickets and receive progress', function () {
    $agent = User::factory()->agent()->create();

    $now = now();
    Carbon::setTestNow($now->copy()->subDays(12));
    $first = Ticket::factory()->open()->create(['subject' => 'Ancient pallet']);
    Carbon::setTestNow($now->copy()->subDays(10));
    $second = Ticket::factory()->open()->create(['subject' => 'Ancient invoice']);
    Carbon::setTestNow($now);

    $fresh = Ticket::factory()->open()->create(['subject' => 'Just opened']);

    $response = TicketsServer::actingAs($agent)
        ->tool(CloseStaleTicketsTool::class, ['days' => 7])
        ->assertOk()
        ->assertSee('Ancient pallet')
        ->assertSee('Ancient invoice')
        ->assertNotificationCount(2)
        ->assertSentNotification('processing/progress', [
            'current' => 1,
            'total' => 2,
            'ticket_id' => $first->id,
        ])
        ->assertSentNotification('processing/progress', [
            'current' => 2,
            'total' => 2,
            'ticket_id' => $second->id,
        ]);

    expect($first->refresh()->status)->toBe(Status::Closed)
        ->and($second->refresh()->status)->toBe(Status::Closed)
        ->and($fresh->refresh()->status)->toBe(Status::Open);

    $texts = [];

    foreach (mcpToolContent($response) as $item) {
        if (is_string($item['text'] ?? null)) {
            $texts[] = $item['text'];
        }
    }

    expect($texts)->toHaveCount(2);
});

test('a requester cannot close stale tickets', function () {
    $requester = User::factory()->requester()->create();

    $now = now();
    Carbon::setTestNow($now->copy()->subDays(10));
    $ticket = Ticket::factory()->for($requester, 'requester')->open()->create();
    Carbon::setTestNow($now);

    TicketsServer::actingAs($requester)
        ->tool(CloseStaleTicketsTool::class, ['days' => 7])
        ->assertHasErrors()
        ->assertSee('Permission denied.');

    expect($ticket->refresh()->status)->toBe(Status::Open);
});

test('missing days is rejected with a sentence', function () {
    $agent = User::factory()->agent()->create();

    TicketsServer::actingAs($agent)
        ->tool(CloseStaleTicketsTool::class)
        ->assertHasErrors()
        ->assertSee('Say how many days a ticket must sit open before it is stale, like 7.');
});
