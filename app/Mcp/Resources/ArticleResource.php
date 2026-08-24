<?php

namespace App\Mcp\Resources;

use App\Models\User;
use App\Repositories\ArticleRepository;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Name('article')]
#[Title('Knowledge base article')]
#[Description('One knowledge base article by slug. Internal slugs are hidden from requesters.')]
#[MimeType('text/markdown')]
class ArticleResource extends Resource implements HasUriTemplate
{
    public function __construct(private ArticleRepository $articles) {}

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('desk://kb/{slug}');
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return Response::error('You must be signed in.');
        }

        $article = $this->articles->findBySlug((string) $request->get('slug'));

        if ($article === null || ! $user->can('view', $article)) {
            return Response::error('No article matches that slug.');
        }

        return Response::text("# {$article->title}\n\n{$article->body}");
    }
}
