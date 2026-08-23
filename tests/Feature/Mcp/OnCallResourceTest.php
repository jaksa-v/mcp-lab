<?php

use App\Mcp\Resources\OnCallResource;
use App\Mcp\Servers\DirectoryServer;
use App\Models\User;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Server\Transport\FakeTransporter;

test('on-call is listed as a static resource', function () {
    $uris = (new DirectoryServer(new FakeTransporter))
        ->createContext()
        ->resources()
        ->map(fn ($resource) => $resource->uri())
        ->all();

    expect($uris)->toContain('directory://on-call');
});

test('on-call names the person currently on the rota', function () {
    $person = User::factory()->onCall()->create(['name' => 'Jonah Hale']);

    DirectoryServer::resource(OnCallResource::class)
        ->assertOk()
        ->assertSee('Jonah Hale')
        ->assertSee('directory://people/'.$person->id);
});

test('on-call advertises last modified', function () {
    $resource = (new DirectoryServer(new FakeTransporter))
        ->createContext()
        ->resources()
        ->sole(fn (Resource $candidate): bool => $candidate->uri() === 'directory://on-call');

    expect($resource->annotations())->toMatchArray(['lastModified' => '2026-08-01T00:00:00Z']);
});
