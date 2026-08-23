<?php

use App\Mcp\Resources\QueueApp;
use App\Mcp\Servers\TicketsServer;
use App\Mcp\Tools\ShowQueueTool;
use App\Models\User;
use Laravel\Mcp\Server\Transport\FakeTransporter;

test('an agent can open the queue app', function () {
    $agent = User::factory()->agent()->create();

    TicketsServer::actingAs($agent)
        ->tool(ShowQueueTool::class)
        ->assertOk()
        ->assertSee('Open queue loaded.');
});

test('show_queue points at the QueueApp resource', function () {
    $agent = User::factory()->agent()->create();

    TicketsServer::actingAs($agent);

    $tool = (new TicketsServer(new FakeTransporter))
        ->createContext()
        ->tools()
        ->sole(fn ($candidate) => $candidate->name() === 'show_queue');

    $meta = $tool->toArray()['_meta']['ui'] ?? [];

    expect($meta['resourceUri'])->toBe(app(QueueApp::class)->uri());
    expect($meta['visibility'])->toBe(['model', 'app']);
});
