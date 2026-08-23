<?php

namespace App\Mcp\Servers;

use App\Mcp\Prompts\DraftReplyPrompt;
use App\Mcp\Prompts\TriageTicketPrompt;
use App\Mcp\Resources\PlaybookResource;
use App\Mcp\Resources\QueueResource;
use App\Mcp\Resources\TicketResource;
use App\Mcp\Tools\AddCommentTool;
use App\Mcp\Tools\AssignTicketTool;
use App\Mcp\Tools\CreateTicketTool;
use App\Mcp\Tools\GetTicketTool;
use App\Mcp\Tools\ListTicketsTool;
use App\Mcp\Tools\SetStatusTool;
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
#[Instructions('Northwind Support tickets. Use list_tickets to see what is open, then get_ticket for the full dossier. Open tickets with create_ticket, then add_comment, set_status, or assign_ticket. Use draft_reply to compose an answer, or triage_ticket to classify a ticket. Triage rules live at desk://playbook. Local Cursor has no HTTP session, so this process signs in as Sam the agent.')]
class TicketsServer extends Server
{
    /**
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        ListTicketsTool::class,
        GetTicketTool::class,
        CreateTicketTool::class,
        AddCommentTool::class,
        SetStatusTool::class,
        AssignTicketTool::class,
    ];

    /**
     * @var array<int, class-string<Server\Resource>>
     */
    protected array $resources = [
        PlaybookResource::class,
        QueueResource::class,
        TicketResource::class,
    ];

    /**
     * @var array<int, class-string<Prompt>>
     */
    protected array $prompts = [
        DraftReplyPrompt::class,
        TriageTicketPrompt::class,
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
