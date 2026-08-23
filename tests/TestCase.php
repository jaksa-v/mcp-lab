<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Fortify\Features;
use Laravel\Mcp\Client;
use Laravel\Mcp\Facades\Mcp;
use Tests\Support\InProcessDirectoryTransport;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensurePassportKeys();

        Mcp::registerClient('directory', fn (): Client => new Client(new InProcessDirectoryTransport));
    }

    protected function ensurePassportKeys(): void
    {
        if (file_exists(storage_path('oauth-private.key'))) {
            return;
        }

        Artisan::call('passport:keys', ['--no-interaction' => true]);
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
