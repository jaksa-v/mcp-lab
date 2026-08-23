<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\GetTicketTool;
use App\Mcp\Tools\ListTicketsTool;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Tool;

#[Name('Northwind Tickets')]
#[Version('0.0.1')]
#[Instructions('Northwind Support tickets. Use list_tickets to see what is open, then get_ticket for the full dossier. Local Cursor has no HTTP session, so this process signs in as Sam the agent.')]
class TicketsServer extends Server
{
    /**
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        ListTicketsTool::class,
        GetTicketTool::class,
    ];

    /**
     * @var array<int, class-string<Server\Resource>>
     */
    protected array $resources = [
        //
    ];

    /**
     * @var array<int, class-string<Prompt>>
     */
    protected array $prompts = [
        //
    ];

    protected function boot(): void
    {
        if (Auth::check()) {
            return;
        }

        $sam = User::query()->where('email', 'sam@northwind.test')->first();

        if ($sam instanceof User) {
            Auth::setUser($sam);
        }
    }
}
