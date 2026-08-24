<?php

use App\Mcp\Resources\ArticleResource;
use App\Mcp\Servers\TicketsServer;
use App\Models\Article;
use App\Models\User;
use Laravel\Mcp\Server\Transport\FakeTransporter;

test('articles are listed as a uri template', function () {
    $server = new TicketsServer(new FakeTransporter);
    $templates = $server->createContext()
        ->resourceTemplates()
        ->map(fn ($resource) => (string) $resource->uriTemplate())
        ->all();

    expect($templates)->toContain('desk://kb/{slug}');
});

test('a requester can read a public article', function () {
    $requester = User::factory()->requester()->create();
    Article::factory()->public()->create([
        'slug' => 'shipping',
        'title' => 'Shipping',
        'body' => 'Ground shipping is 3 to 5 days.',
    ]);

    TicketsServer::actingAs($requester)
        ->resource(ArticleResource::class, ['slug' => 'shipping'])
        ->assertOk()
        ->assertSee('# Shipping')
        ->assertSee('Ground shipping is 3 to 5 days.');
});

test('a requester cannot read an internal article', function () {
    $requester = User::factory()->requester()->create();
    Article::factory()->internal()->create([
        'slug' => 'refund-abuse',
        'title' => 'Refund abuse',
    ]);

    TicketsServer::actingAs($requester)
        ->resource(ArticleResource::class, ['slug' => 'refund-abuse'])
        ->assertHasErrors()
        ->assertSee('No article matches that slug.');
});

test('an agent can read an internal article', function () {
    $agent = User::factory()->agent()->create();
    Article::factory()->internal()->create([
        'slug' => 'refund-abuse',
        'title' => 'Refund abuse',
        'body' => 'More than two refunds in 90 days is a review.',
    ]);

    TicketsServer::actingAs($agent)
        ->resource(ArticleResource::class, ['slug' => 'refund-abuse'])
        ->assertOk()
        ->assertSee('Refund abuse')
        ->assertSee('More than two refunds in 90 days is a review.');
});

test('a missing article returns a useful error', function () {
    $agent = User::factory()->agent()->create();

    TicketsServer::actingAs($agent)
        ->resource(ArticleResource::class, ['slug' => 'no-such-article'])
        ->assertHasErrors()
        ->assertSee('No article matches that slug.');
});
