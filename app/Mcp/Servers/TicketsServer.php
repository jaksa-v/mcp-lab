<?php

namespace App\Mcp\Servers;

use App\Mcp\Prompts\DraftReplyPrompt;
use App\Mcp\Prompts\TriageTicketPrompt;
use App\Mcp\Prompts\WeeklyReviewPrompt;
use App\Mcp\Resources\ArticleResource;
use App\Mcp\Resources\EscalationResource;
use App\Mcp\Resources\PlaybookResource;
use App\Mcp\Resources\QueueApp;
use App\Mcp\Resources\QueueResource;
use App\Mcp\Resources\TicketResource;
use App\Mcp\Tools\AddCommentTool;
use App\Mcp\Tools\AssignTicketTool;
use App\Mcp\Tools\CloseStaleTicketsTool;
use App\Mcp\Tools\CreateTicketTool;
use App\Mcp\Tools\DeleteTicketTool;
use App\Mcp\Tools\GetQueueDataTool;
use App\Mcp\Tools\GetTicketTool;
use App\Mcp\Tools\ListTicketsTool;
use App\Mcp\Tools\SearchKbTool;
use App\Mcp\Tools\SetStatusTool;
use App\Mcp\Tools\ShowQueueTool;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Icon;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Tool;

#[Name('Northwind Tickets')]
#[Version('0.0.1')]
#[Icon('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect width="32" height="32" fill="%23111827"/></svg>', mimeType: 'image/svg+xml', sizes: ['32x32'])]
#[Instructions('Northwind Support tickets. Use list_tickets to see what is open, then get_ticket for the full dossier. Open tickets with create_ticket, then add_comment, set_status, or assign_ticket. Search the knowledge base with search_kb, then read desk://kb/{slug}. Use draft_reply to compose an answer, or triage_ticket to classify a ticket. Admins can delete_ticket. Staff can use weekly_review and desk://kb/escalation. Triage rules live at desk://playbook. Call show_queue for the interactive open queue. Local Cursor has no HTTP session, so this process signs in as Sam the agent.')]
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
        DeleteTicketTool::class,
        CloseStaleTicketsTool::class,
        SearchKbTool::class,
        ShowQueueTool::class,
        GetQueueDataTool::class,
    ];

    /**
     * @var array<int, class-string<Server\Resource>>
     */
    protected array $resources = [
        PlaybookResource::class,
        QueueResource::class,
        TicketResource::class,
        ArticleResource::class,
        EscalationResource::class,
        QueueApp::class,
    ];

    /**
     * @var array<int, class-string<Prompt>>
     */
    protected array $prompts = [
        DraftReplyPrompt::class,
        TriageTicketPrompt::class,
        WeeklyReviewPrompt::class,
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
