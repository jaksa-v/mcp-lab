<?php

use App\Mcp\Resources\EscalationResource;
use App\Mcp\Servers\TicketsServer;
use App\Models\Article;
use App\Models\User;
use Laravel\Mcp\Server\Transport\FakeTransporter;

test('a requester does not see the escalation resource', function () {
    $requester = User::factory()->requester()->create();

    TicketsServer::actingAs($requester);

    $uris = (new TicketsServer(new FakeTransporter))
        ->createContext()
        ->resources()
        ->map(fn ($resource) => $resource->uri())
        ->all();

    expect($uris)->toContain('desk://playbook');
    expect($uris)->not->toContain('desk://kb/escalation');
});

test('an agent sees the escalation resource', function () {
    $agent = User::factory()->agent()->create();

    TicketsServer::actingAs($agent);

    $uris = (new TicketsServer(new FakeTransporter))
        ->createContext()
        ->resources()
        ->map(fn ($resource) => $resource->uri())
        ->all();

    expect($uris)->toContain('desk://kb/escalation');
});

test('an agent can read the escalation article', function () {
    $agent = User::factory()->agent()->create();
    Article::factory()->internal()->create([
        'slug' => 'escalation',
        'title' => 'Escalation',
        'body' => 'Escalate to the on-call agent after two failed contacts.',
    ]);

    TicketsServer::actingAs($agent)
        ->resource(EscalationResource::class)
        ->assertOk()
        ->assertName('escalation')
        ->assertSee('Escalation')
        ->assertSee('Escalate to the on-call agent after two failed contacts.');
});

test('a requester cannot read the escalation article', function () {
    $requester = User::factory()->requester()->create();
    Article::factory()->internal()->create([
        'slug' => 'escalation',
        'title' => 'Escalation',
    ]);

    TicketsServer::actingAs($requester)
        ->resource(EscalationResource::class)
        ->assertHasErrors()
        ->assertSee('No article matches that slug.');
});
