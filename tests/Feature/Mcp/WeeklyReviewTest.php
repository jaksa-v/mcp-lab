<?php

use App\Mcp\Prompts\WeeklyReviewPrompt;
use App\Mcp\Servers\TicketsServer;
use App\Models\Ticket;
use App\Models\User;
use Laravel\Mcp\Server\Transport\FakeTransporter;

test('a requester does not see weekly_review', function () {
    $requester = User::factory()->requester()->create();

    TicketsServer::actingAs($requester);

    $names = (new TicketsServer(new FakeTransporter))
        ->createContext()
        ->prompts()
        ->map(fn ($prompt) => $prompt->name())
        ->all();

    expect($names)->toContain('draft_reply');
    expect($names)->not->toContain('weekly_review');
});

test('an agent sees weekly_review', function () {
    $agent = User::factory()->agent()->create();

    TicketsServer::actingAs($agent);

    $names = (new TicketsServer(new FakeTransporter))
        ->createContext()
        ->prompts()
        ->map(fn ($prompt) => $prompt->name())
        ->all();

    expect($names)->toContain('weekly_review');
});

test('an agent can run weekly_review against open tickets', function () {
    $agent = User::factory()->agent()->create();
    Ticket::factory()->open()->create([
        'subject' => 'Chargeback on order 4412',
    ]);

    TicketsServer::actingAs($agent)
        ->prompt(WeeklyReviewPrompt::class)
        ->assertOk()
        ->assertName('weekly_review')
        ->assertSee('weekly review for staff')
        ->assertSee('Chargeback on order 4412');
});

test('a requester cannot invoke weekly_review', function () {
    $requester = User::factory()->requester()->create();

    TicketsServer::actingAs($requester)
        ->prompt(WeeklyReviewPrompt::class)
        ->assertHasErrors()
        ->assertSee('Prompt [weekly_review] not found.');
});
