<?php

namespace App\Mcp\Resources;

use Laravel\Mcp\Enums\Role;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Annotations\Audience;
use Laravel\Mcp\Server\Annotations\Priority;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Name('org')]
#[Title('Northwind org')]
#[Description('Who Northwind Support is and how the directory is laid out.')]
#[Uri('directory://org')]
#[MimeType('text/markdown')]
#[Priority(0.9)]
#[Audience(Role::Assistant)]
class OrgResource extends Resource
{
    public function handle(): Response
    {
        return Response::text(<<<'MARKDOWN'
# Northwind Traders

This directory is read-only. Tickets live on the other server.

## People

- Search: `search_people`
- One person: `get_person` or `directory://people/{id}`
- On call: `who_is_on_call` or `directory://on-call`

## Teams

Support, Billing, and Warehouse. List them with `list_teams` or read `directory://teams/{slug}`.
MARKDOWN);
    }
}
