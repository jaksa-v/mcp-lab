<?php

namespace App\Mcp\Servers;

use App\Mcp\Resources\BadgeResource;
use App\Mcp\Resources\OnCallResource;
use App\Mcp\Resources\OrgResource;
use App\Mcp\Resources\PersonResource;
use App\Mcp\Resources\TeamResource;
use App\Mcp\Tools\GetPersonTool;
use App\Mcp\Tools\ListTeamsTool;
use App\Mcp\Tools\SearchPeopleTool;
use App\Mcp\Tools\WhoIsOnCallTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Icon;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;

#[Name('Northwind Directory')]
#[Version('0.0.1')]
#[Icon('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect width="32" height="32" fill="%230f766e"/></svg>', mimeType: 'image/svg+xml', sizes: ['32x32'])]
#[Instructions('Read-only Northwind people and teams. Search with search_people, look up one person with get_person, list teams with list_teams, or ask who_is_on_call. Dossiers live at directory://people/{id} and directory://teams/{slug}. The org blurb is directory://org. This server does not write anything.')]
class DirectoryServer extends Server
{
    /**
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        SearchPeopleTool::class,
        GetPersonTool::class,
        ListTeamsTool::class,
        WhoIsOnCallTool::class,
    ];

    /**
     * @var array<int, class-string<Server\Resource>>
     */
    protected array $resources = [
        OrgResource::class,
        PersonResource::class,
        TeamResource::class,
        OnCallResource::class,
        BadgeResource::class,
    ];
}
