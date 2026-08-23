<?php

use App\Mcp\Servers\DirectoryServer;
use App\Mcp\Tools\ListTeamsTool;
use App\Models\Team;

test('list_teams returns names and slugs as two text payloads', function () {
    Team::factory()->create(['name' => 'Support', 'slug' => 'support']);
    Team::factory()->create(['name' => 'Billing', 'slug' => 'billing']);

    $response = DirectoryServer::tool(ListTeamsTool::class)
        ->assertOk()
        ->assertSee('Support')
        ->assertSee('Billing')
        ->assertSee('support')
        ->assertSee('billing');

    $texts = array_values(array_filter(
        mcpToolContent($response),
        fn (array $item): bool => ($item['type'] ?? null) === 'text',
    ));

    expect($texts)->toHaveCount(2);
    expect($texts[0]['text'] ?? '')->toContain('Teams:');
    expect($texts[1]['text'] ?? '')->toContain('Slugs:');
});

test('list_teams still returns two texts when empty', function () {
    $response = DirectoryServer::tool(ListTeamsTool::class)->assertOk();

    $texts = array_values(array_filter(
        mcpToolContent($response),
        fn (array $item): bool => ($item['type'] ?? null) === 'text',
    ));

    expect($texts)->toHaveCount(2);
});
