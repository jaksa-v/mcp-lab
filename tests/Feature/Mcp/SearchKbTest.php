<?php

use App\Mcp\Servers\TicketsServer;
use App\Mcp\Tools\SearchKbTool;
use App\Models\Article;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;

test('search_kb returns public articles as structured output', function () {
    $requester = User::factory()->requester()->create();
    Article::factory()->public()->create([
        'slug' => 'refunds',
        'title' => 'Refunds',
        'body' => 'Refunds take 5 to 7 business days.',
    ]);
    Article::factory()->internal()->create([
        'slug' => 'refund-abuse',
        'title' => 'Refund abuse',
    ]);

    TicketsServer::actingAs($requester)
        ->tool(SearchKbTool::class, ['query' => 'refund'])
        ->assertOk()
        ->assertSee('Refunds')
        ->assertDontSee('Refund abuse')
        ->assertStructuredContent(function (AssertableJson $json): void {
            $json->has('articles', 1)
                ->where('articles.0.slug', 'refunds')
                ->where('articles.0.uri', 'desk://kb/refunds');
        });
});

test('an agent also sees internal articles', function () {
    $agent = User::factory()->agent()->create();
    Article::factory()->internal()->create([
        'slug' => 'refund-abuse',
        'title' => 'Refund abuse',
        'body' => 'More than two refunds in 90 days is a review.',
    ]);

    TicketsServer::actingAs($agent)
        ->tool(SearchKbTool::class, ['query' => 'refund'])
        ->assertOk()
        ->assertSee('Refund abuse');
});

test('search_kb with no matches says so', function () {
    $agent = User::factory()->agent()->create();

    TicketsServer::actingAs($agent)
        ->tool(SearchKbTool::class, ['query' => 'nobody-here'])
        ->assertOk()
        ->assertSee('No articles match.');
});

test('search_kb requires a query sentence', function () {
    $agent = User::factory()->agent()->create();

    TicketsServer::actingAs($agent)
        ->tool(SearchKbTool::class, [])
        ->assertHasErrors()
        ->assertSee('Give me a word or phrase to search the knowledge base for.');
});
