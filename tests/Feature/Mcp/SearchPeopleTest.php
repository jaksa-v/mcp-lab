<?php

use App\Mcp\Servers\DirectoryServer;
use App\Mcp\Tools\SearchPeopleTool;
use App\Models\Team;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;

test('search_people returns matching people as structured output', function () {
    $team = Team::factory()->create(['name' => 'Support', 'slug' => 'support']);
    User::factory()->agent()->forTeam($team)->create([
        'name' => 'Sam Carter',
        'email' => 'sam@northwind.test',
        'title' => 'Support agent',
    ]);
    User::factory()->create(['name' => 'Ada Lovelace']);

    DirectoryServer::tool(SearchPeopleTool::class, ['query' => 'Sam'])
        ->assertOk()
        ->assertSee('Sam Carter')
        ->assertStructuredContent(function (AssertableJson $json): void {
            $json->has('people', 1)
                ->where('people.0.name', 'Sam Carter')
                ->where('people.0.email', 'sam@northwind.test')
                ->where('people.0.team', 'support');
        });
});

test('search_people can narrow by team slug', function () {
    $support = Team::factory()->create(['slug' => 'support']);
    $billing = Team::factory()->create(['slug' => 'billing']);
    User::factory()->forTeam($support)->create(['name' => 'Sam Support']);
    User::factory()->forTeam($billing)->create(['name' => 'Sam Billing']);

    DirectoryServer::tool(SearchPeopleTool::class, [
        'query' => 'Sam',
        'team' => 'billing',
    ])
        ->assertOk()
        ->assertSee('Sam Billing')
        ->assertDontSee('Sam Support');
});

test('search_people with no matches says so', function () {
    DirectoryServer::tool(SearchPeopleTool::class, ['query' => 'nobody-here'])
        ->assertOk()
        ->assertSee('No people match.');
});

test('search_people requires a query sentence', function () {
    DirectoryServer::tool(SearchPeopleTool::class, [])
        ->assertHasErrors()
        ->assertSee('Give me a name, email, or title to search for.');
});
