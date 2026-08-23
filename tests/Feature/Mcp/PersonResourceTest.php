<?php

use App\Mcp\Resources\PersonResource;
use App\Mcp\Servers\DirectoryServer;
use App\Models\Team;
use App\Models\User;
use Laravel\Mcp\Server\Transport\FakeTransporter;

test('people are listed as a uri template', function () {
    $templates = (new DirectoryServer(new FakeTransporter))
        ->createContext()
        ->resourceTemplates()
        ->map(fn ($resource) => (string) $resource->uriTemplate())
        ->all();

    expect($templates)->toContain('directory://people/{id}');
});

test('a person dossier can be read by uri', function () {
    $team = Team::factory()->create(['name' => 'Support']);
    $person = User::factory()->agent()->forTeam($team)->create([
        'name' => 'Sam Carter',
        'email' => 'sam@northwind.test',
        'title' => 'Support agent',
    ]);

    DirectoryServer::resource(PersonResource::class, ['id' => $person->id])
        ->assertOk()
        ->assertSee('# Sam Carter')
        ->assertSee('sam@northwind.test')
        ->assertSee('Support');
});

test('a person without a team still has a dossier', function () {
    $person = User::factory()->requester()->create([
        'name' => 'Ada Lovelace',
        'team_id' => null,
    ]);

    DirectoryServer::resource(PersonResource::class, ['id' => $person->id])
        ->assertOk()
        ->assertSee('# Ada Lovelace')
        ->assertSee('No team');
});

test('a missing person resource returns a useful error', function () {
    DirectoryServer::resource(PersonResource::class, ['id' => 9999])
        ->assertHasErrors()
        ->assertSee('No person matches that id.');
});
