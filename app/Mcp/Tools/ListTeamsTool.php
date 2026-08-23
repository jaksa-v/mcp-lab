<?php

namespace App\Mcp\Tools;

use App\Repositories\DirectoryRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list_teams')]
#[Description('List Northwind teams by name and slug.')]
#[IsReadOnly]
#[IsIdempotent]
class ListTeamsTool extends Tool
{
    public function __construct(private DirectoryRepository $directory) {}

    public function handle(): ResponseFactory
    {
        $teams = $this->directory->listTeams();

        if ($teams->isEmpty()) {
            return Response::make([
                Response::text('No teams are registered.'),
                Response::text('Add a team before assigning tickets to one.'),
            ]);
        }

        return Response::make([
            Response::text('Teams: '.$teams->pluck('name')->implode(', ').'.'),
            Response::text('Slugs: '.$teams->pluck('slug')->implode(', ').'.'),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
