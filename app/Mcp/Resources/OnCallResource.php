<?php

namespace App\Mcp\Resources;

use App\Models\User;
use App\Repositories\DirectoryRepository;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Annotations\LastModified;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Name('on-call')]
#[Title('On-call person')]
#[Description('Who is currently marked on call.')]
#[Uri('directory://on-call')]
#[MimeType('text/markdown')]
#[LastModified('2026-08-01T00:00:00Z')]
class OnCallResource extends Resource
{
    public function __construct(private DirectoryRepository $directory) {}

    public function handle(): Response
    {
        $person = $this->directory->onCallPerson();

        if (! $person instanceof User) {
            return Response::text("# On call\n\nNobody is on call.");
        }

        return Response::text("# On call\n\n{$person->name} (directory://people/{$person->id})");
    }
}
