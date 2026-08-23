<?php

use App\Mcp\Servers\DirectoryServer;
use App\Mcp\Tools\GetPersonTool;
use App\Models\Team;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;

test('get_person returns a structured dossier', function () {
    $team = Team::factory()->create(['slug' => 'support']);
    $person = User::factory()->agent()->forTeam($team)->create([
        'name' => 'Sam Carter',
        'email' => 'sam@northwind.test',
        'title' => 'Support agent',
    ]);

    DirectoryServer::tool(GetPersonTool::class, ['id' => $person->id])
        ->assertOk()
        ->assertSee('Sam Carter')
        ->assertStructuredContent(function (AssertableJson $json) use ($person): void {
            $json->where('person.id', $person->id)
                ->where('person.name', 'Sam Carter')
                ->where('person.email', 'sam@northwind.test')
                ->where('person.role', 'agent')
                ->where('person.team', 'support');
        });
});

test('a missing person is rejected with a sentence', function () {
    DirectoryServer::tool(GetPersonTool::class, ['id' => 9999])
        ->assertHasErrors()
        ->assertSee('No person matches that id.');
});

test('get_person requires an id sentence', function () {
    DirectoryServer::tool(GetPersonTool::class, [])
        ->assertHasErrors()
        ->assertSee('Give me the person id, like 4.');
});
