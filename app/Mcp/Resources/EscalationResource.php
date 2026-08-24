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
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Name('escalation')]
#[Title('Escalation playbook')]
#[Description('Internal notes on when to escalate a ticket. Agents and admins only.')]
#[Uri('desk://kb/escalation')]
#[MimeType('text/markdown')]
class EscalationResource extends Resource
{
    public function __construct(private ArticleRepository $articles) {}

    public function shouldRegister(Request $request): bool
    {
        $user = $request->user();

        return $user instanceof User && $user->role->isStaff();
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return Response::error('You must be signed in.');
        }

        $article = $this->articles->findBySlug('escalation');

        if ($article === null) {
            return Response::error('No escalation article is published.');
        }

        if (! $user->can('view', $article)) {
            return Response::error('Permission denied.');
        }

        return Response::text("# {$article->title}\n\n{$article->body}");
    }
}
