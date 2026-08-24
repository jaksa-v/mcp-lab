<?php

namespace App\Mcp\Tools;

use App\Models\Article;
use App\Models\User;
use App\Repositories\ArticleRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('search_kb')]
#[Description('Search the knowledge base. Requesters only see public articles. Agents also see internal ones.')]
#[IsReadOnly]
class SearchKbTool extends Tool
{
    public function __construct(private ArticleRepository $articles) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return Response::error('You must be signed in.');
        }

        $validated = $request->validate([
            'query' => ['required', 'string'],
        ], [
            'query.required' => 'Give me a word or phrase to search the knowledge base for.',
        ]);

        $articles = $this->articles->searchVisibleTo($user, $validated['query']);

        if ($articles->isEmpty()) {
            return Response::text('No articles match.');
        }

        return Response::structured([
            'articles' => $articles->map($this->toListedArticle(...))->values()->all(),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('A word or phrase from the article title or body.')
                ->required(),
        ];
    }

    /**
     * @return array<string, Type>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'articles' => $schema->array()
                ->items($schema->object([
                    'slug' => $schema->string()->required(),
                    'title' => $schema->string()->required(),
                    'visibility' => $schema->string()->enum(['public', 'internal'])->required(),
                    'uri' => $schema->string()->description('desk://kb/{slug}')->required(),
                ]))
                ->required(),
        ];
    }

    /**
     * @return array{slug: string, title: string, visibility: string, uri: string}
     */
    private function toListedArticle(Article $article): array
    {
        return [
            'slug' => $article->slug,
            'title' => $article->title,
            'visibility' => $article->visibility->value,
            'uri' => 'desk://kb/'.$article->slug,
        ];
    }
}
