<?php

use App\Mcp\Resources\BadgeResource;
use App\Mcp\Servers\DirectoryServer;
use Illuminate\Support\Facades\Storage;
use Laravel\Mcp\Server\Transport\FakeTransporter;

test('the badge is listed as a static png resource', function () {
    $uris = (new DirectoryServer(new FakeTransporter))
        ->createContext()
        ->resources()
        ->map(fn ($resource) => $resource->uri())
        ->all();

    expect($uris)->toContain('directory://badge');
    expect(app(BadgeResource::class)->mimeType())->toBe('image/png');
});

test('the badge is served from storage', function () {
    Storage::disk('local')->put(
        'badge.png',
        (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true),
    );

    DirectoryServer::resource(BadgeResource::class)->assertOk();
});
