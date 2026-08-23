<?php

namespace App\Mcp\Resources;

use App\Models\Team;
use App\Models\User;
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

#[Name('person')]
#[Title('Person dossier')]
#[Description('Markdown dossier for one Northwind person.')]
#[MimeType('text/markdown')]
class PersonResource extends Resource implements HasUriTemplate
{
    public function __construct(private DirectoryRepository $directory) {}

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('directory://people/{id}');
    }

    public function handle(Request $request): Response
    {
        $person = $this->directory->findPerson((int) $request->get('id'));

        if (! $person instanceof User) {
            return Response::error('No person matches that id.');
        }

        $team = $person->team instanceof Team ? $person->team->name : 'No team';
        $title = $person->title ?? 'None';
        $onCall = $person->on_call ? 'yes' : 'no';

        return Response::text(implode("\n", [
            "# {$person->name}",
            "- Email: {$person->email}",
            '- Role: '.$person->role->value,
            "- Team: {$team}",
            "- Title: {$title}",
            "- On call: {$onCall}",
        ]));
    }
}
