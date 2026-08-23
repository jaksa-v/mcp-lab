<?php

use App\Mcp\Resources\PlaybookResource;
use App\Mcp\Resources\QueueResource;
use App\Mcp\Servers\TicketsServer;
use App\Models\User;
use Laravel\Mcp\Server\Transport\FakeTransporter;

test('the playbook is listed as a static resource', function () {
    $server = new TicketsServer(new FakeTransporter);
    $uris = $server->createContext()
        ->resources()
        ->map(fn ($resource) => $resource->uri())
        ->all();

    expect($uris)
        ->toContain('desk://playbook')
        ->toContain('desk://queue');
});

test('an agent can read the triage playbook', function () {
    $agent = User::factory()->agent()->create();

    TicketsServer::actingAs($agent)
        ->resource(PlaybookResource::class)
        ->assertOk()
        ->assertName('playbook')
        ->assertSee('Northwind Support playbook')
        ->assertSee('How to triage')
        ->assertSee('desk://queue');
});

test('the playbook is markdown at a fixed uri', function () {
    $playbook = app(PlaybookResource::class);

    expect($playbook->uri())->toBe('desk://playbook');
    expect($playbook->mimeType())->toBe('text/markdown');
});

test('queue is also listed as a static resource', function () {
    $queue = app(QueueResource::class);

    expect($queue->uri())->toBe('desk://queue');
    expect($queue->mimeType())->toBe('text/markdown');
});
