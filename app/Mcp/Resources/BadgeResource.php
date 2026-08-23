<?php

namespace App\Mcp\Resources;

use InvalidArgumentException;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Name('badge')]
#[Title('Northwind badge')]
#[Description('Tiny PNG badge for the helpdesk.')]
#[Uri('directory://badge')]
#[MimeType('image/png')]
class BadgeResource extends Resource
{
    public function handle(): Response
    {
        try {
            return Response::fromStorage('badge.png');
        } catch (InvalidArgumentException) {
            return Response::error('The badge file is missing.');
        }
    }
}
