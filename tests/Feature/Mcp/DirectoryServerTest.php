<?php

use App\Mcp\Servers\DirectoryServer;
use Laravel\Mcp\Server\Transport\FakeTransporter;

test('the directory server advertises an icon', function () {
    $icons = (new DirectoryServer(new FakeTransporter))
        ->createContext()
        ->implementation
        ->toArray()['icons'] ?? [];

    expect($icons)->toHaveCount(1)
        ->and($icons[0]['src'])->toStartWith('data:image/svg+xml')
        ->and($icons[0]['mimeType'])->toBe('image/svg+xml')
        ->and($icons[0]['sizes'])->toBe(['32x32']);
});

test('the directory server says it is read-only', function () {
    $instructions = (new DirectoryServer(new FakeTransporter))
        ->createContext()
        ->instructions;

    expect($instructions)->toContain('Read-only');
});
