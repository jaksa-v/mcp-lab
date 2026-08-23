<?php

use App\Mcp\Resources\QueueApp;
use App\Mcp\Servers\TicketsServer;
use App\Models\User;
use Laravel\Mcp\Server\Transport\FakeTransporter;
use Laravel\Mcp\Server\Ui\Enums\Library;

test('the queue app is listed as an html ui resource', function () {
    $agent = User::factory()->agent()->create();

    TicketsServer::actingAs($agent);

    $uris = (new TicketsServer(new FakeTransporter))
        ->createContext()
        ->resources()
        ->map(fn ($resource) => $resource->uri())
        ->all();

    $app = app(QueueApp::class);

    expect($uris)->toContain($app->uri());
    expect($app->uri())->toStartWith('ui://');
    expect($app->mimeType())->toBe('text/html;profile=mcp-app');
});

test('an agent can read the queue app html', function () {
    $agent = User::factory()->agent()->create();

    TicketsServer::actingAs($agent)
        ->resource(QueueApp::class)
        ->assertOk()
        ->assertSee('Open queue')
        ->assertSee('get_queue_data')
        ->assertSee('id="run-btn"')
        ->assertSee('cdn.tailwindcss.com')
        ->assertSee('alpinejs');
});

test('queue app meta includes tailwind and alpine', function () {
    $meta = app(QueueApp::class)->resolvedAppMeta();

    expect($meta['csp']['resourceDomains'] ?? [])
        ->toContain(Library::Tailwind->domains()[0])
        ->toContain(Library::Alpine->domains()[0]);
});

test('the tickets server advertises the ui capability', function () {
    $server = new TicketsServer(new FakeTransporter);
    $server->start();

    expect($server->createContext()->serverCapabilities)
        ->toHaveKey(TicketsServer::CAPABILITY_UI);
});
