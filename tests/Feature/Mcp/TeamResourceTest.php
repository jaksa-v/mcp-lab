<?php

use App\Mcp\Resources\TeamResource;
use App\Mcp\Servers\DirectoryServer;
use App\Models\Team;
use Laravel\Mcp\Server\Transport\FakeTransporter;

test('teams are listed as a uri template', function () {
    $templates = (new DirectoryServer(new FakeTransporter))
        ->createContext()
        ->resourceTemplates()
        ->map(fn ($resource) => (string) $resource->uriTemplate())
        ->all();

    expect($templates)->toContain('directory://teams/{slug}');
});

test('a team dossier can be read by slug', function () {
    Team::factory()->create(['name' => 'Billing', 'slug' => 'billing']);

    DirectoryServer::resource(TeamResource::class, ['slug' => 'billing'])
        ->assertOk()
        ->assertSee('# Billing')
        ->assertSee('billing');
});

test('a missing team resource returns a useful error', function () {
    DirectoryServer::resource(TeamResource::class, ['slug' => 'nope'])
        ->assertHasErrors()
        ->assertSee('No team matches that slug.');
});
