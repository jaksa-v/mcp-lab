<?php

namespace App\Mcp\Tools;

use App\Models\User;
use App\Repositories\DirectoryRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get_person')]
#[Description('Look up one Northwind person by user id.')]
#[IsReadOnly]
#[IsIdempotent]
class GetPersonTool extends Tool
{
    public function __construct(private DirectoryRepository $directory) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
        ], [
            'id.required' => 'Give me the person id, like 4.',
            'id.integer' => 'Person id must be a number.',
        ]);

        $person = $this->directory->findPerson((int) $validated['id']);

        if (! $person instanceof User) {
            return Response::error('No person matches that id.');
        }

        return Response::structured([
            'person' => $this->directory->toPerson($person),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('The user id from search_people.')
                ->required(),
        ];
    }
}
