<?php

namespace App\Mcp\Tools;

use App\Models\Team;
use App\Models\User;
use App\Repositories\DirectoryRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('who_is_on_call')]
#[Description('Return the person currently marked on call. Fake rota, real lookup.')]
#[IsReadOnly]
#[IsOpenWorld]
class WhoIsOnCallTool extends Tool
{
    public function __construct(private DirectoryRepository $directory) {}

    public function handle(): Response
    {
        $person = $this->directory->onCallPerson();

        if (! $person instanceof User) {
            return Response::text('Nobody is on call.');
        }

        $team = $person->team instanceof Team ? $person->team->name : 'no team';

        return Response::text("{$person->name} is on call ({$team}).");
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
