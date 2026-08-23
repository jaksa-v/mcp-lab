<?php

use App\Mcp\Resources\OrgResource;
use App\Mcp\Servers\DirectoryServer;
use Laravel\Mcp\Server\Transport\FakeTransporter;

test('the org blurb is listed as a static resource', function () {
    $uris = (new DirectoryServer(new FakeTransporter))
        ->createContext()
        ->resources()
        ->map(fn ($resource) => $resource->uri())
        ->all();

    expect($uris)->toContain('directory://org');
});

test('the org blurb is markdown for the assistant', function () {
    DirectoryServer::resource(OrgResource::class)
        ->assertOk()
        ->assertName('org')
        ->assertSee('Northwind Traders')
        ->assertSee('directory://people/{id}');

    $org = app(OrgResource::class);

    expect($org->uri())->toBe('directory://org');
    expect($org->mimeType())->toBe('text/markdown');
});
