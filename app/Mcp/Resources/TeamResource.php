<?php

namespace App\Mcp\Resources;

use App\Models\Team;
use App\Repositories\DirectoryRepository;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Name('team')]
#[Title('Team dossier')]
#[Description('Markdown dossier for one Northwind team.')]
#[MimeType('text/markdown')]
class TeamResource extends Resource implements HasUriTemplate
{
    public function __construct(private DirectoryRepository $directory) {}

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('directory://teams/{slug}');
    }

    public function handle(Request $request): Response
    {
        $slug = (string) $request->get('slug');
        $team = $this->directory->findTeamBySlug($slug);

        if (! $team instanceof Team) {
            return Response::error('No team matches that slug.');
        }

        return Response::text(implode("\n", [
            "# {$team->name}",
            "- Slug: {$team->slug}",
            '- People: directory://people/{id}',
        ]));
    }
}
