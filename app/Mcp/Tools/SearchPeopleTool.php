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

#[Name('search_people')]
#[Description('Search Northwind people by name, email, or title. Optionally narrow to a team slug.')]
#[IsReadOnly]
#[IsIdempotent]
class SearchPeopleTool extends Tool
{
    public function __construct(private DirectoryRepository $directory) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'query' => ['required', 'string'],
            'team' => ['sometimes', 'nullable', 'string'],
        ], [
            'query.required' => 'Give me a name, email, or title to search for.',
        ]);

        $people = $this->directory->searchPeople(
            $validated['query'],
            $validated['team'] ?? null,
        );

        if ($people->isEmpty()) {
            return Response::text('No people match.');
        }

        $payload = [
            'people' => $people->map(fn (User $person): array => $this->directory->toPerson($person))->values()->all(),
        ];

        return Response::structured($payload);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Name, email, or title fragment.')
                ->required(),
            'team' => $schema->string()
                ->description('Optional team slug, like support or billing.'),
        ];
    }

    /**
     * @return array<string, Type>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'people' => $schema->array()
                ->items($schema->object([
                    'id' => $schema->integer()->required(),
                    'name' => $schema->string()->required(),
                    'email' => $schema->string()->required(),
                    'title' => $schema->string()->nullable(),
                    'role' => $schema->string()->required(),
                    'team' => $schema->string()->nullable(),
                    'on_call' => $schema->boolean()->required(),
                ]))
                ->required(),
        ];
    }
}
