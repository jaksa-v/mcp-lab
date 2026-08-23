<?php

use App\Mcp\Servers\TicketsServer;
use Laravel\Mcp\Server\Transport\FakeTransporter;

test('the tickets server advertises an icon', function () {
    $icons = (new TicketsServer(new FakeTransporter))
        ->createContext()
        ->implementation
        ->toArray()['icons'] ?? [];

    expect($icons)->toHaveCount(1)
        ->and($icons[0]['src'])->toStartWith('data:image/svg+xml')
        ->and($icons[0]['mimeType'])->toBe('image/svg+xml')
        ->and($icons[0]['sizes'])->toBe(['32x32']);
});
