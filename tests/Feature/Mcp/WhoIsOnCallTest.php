<?php

use App\Mcp\Servers\DirectoryServer;
use App\Mcp\Tools\WhoIsOnCallTool;
use App\Models\Team;
use App\Models\User;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Transport\FakeTransporter;

test('who_is_on_call names the on-call person', function () {
    $team = Team::factory()->create(['name' => 'Support']);
    User::factory()->agent()->forTeam($team)->onCall()->create([
        'name' => 'Jonah Hale',
    ]);

    DirectoryServer::tool(WhoIsOnCallTool::class)
        ->assertOk()
        ->assertSee('Jonah Hale is on call (Support).');
});

test('who_is_on_call says so when the rota is empty', function () {
    DirectoryServer::tool(WhoIsOnCallTool::class)
        ->assertOk()
        ->assertSee('Nobody is on call.');
});

test('who_is_on_call is marked open world', function () {
    $tool = (new DirectoryServer(new FakeTransporter))
        ->createContext()
        ->tools()
        ->sole(fn (Tool $candidate): bool => $candidate->name() === 'who_is_on_call');

    expect($tool->annotations())->toMatchArray(['openWorldHint' => true]);
});
