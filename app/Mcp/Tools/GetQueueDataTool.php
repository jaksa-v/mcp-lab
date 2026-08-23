<?php

namespace App\Mcp\Tools;

use App\Enums\Status;
use App\Mcp\Resources\QueueApp;
use App\Models\Ticket;
use App\Models\User;
use App\Repositories\TicketRepository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\RendersApp;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Ui\Enums\Visibility;

#[Name('get_queue_data')]
#[Description('Open tickets for the QueueApp iframe. Not for the model; use list_tickets instead.')]
#[IsReadOnly]
#[RendersApp(resource: QueueApp::class, visibility: [Visibility::App])]
class GetQueueDataTool extends Tool
{
    public function __construct(private TicketRepository $tickets) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return Response::error('You must be signed in.');
        }

        if (! $user->can('viewAny', Ticket::class)) {
            return Response::error('Permission denied.');
        }

        $tickets = $this->tickets->listVisibleTo($user, Status::Open);

        return Response::structured([
            'tickets' => $tickets->map($this->toRow(...))->values()->all(),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    /**
     * @return array<string, Type>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'tickets' => $schema->array()
                ->items($schema->object([
                    'id' => $schema->integer()->description('Ticket id.')->required(),
                    'subject' => $schema->string()->description('Short subject.')->required(),
                    'status' => $schema->string()->enum(['open', 'pending', 'closed'])->required(),
                    'priority' => $schema->string()->enum(['low', 'normal', 'high', 'urgent'])->required(),
                    'requester' => $schema->string()->description('Requester name.')->required(),
                    'assignee' => $schema->string()->description('Assignee name, if any.')->nullable(),
                ]))
                ->description('Open tickets the current user can see.')
                ->required(),
        ];
    }

    /**
     * @return array{id: int, subject: string, status: string, priority: string, requester: string, assignee: string|null}
     */
    private function toRow(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'status' => $ticket->status->value,
            'priority' => $ticket->priority->value,
            'requester' => $ticket->requester->name,
            'assignee' => $ticket->assignee_id !== null ? $ticket->assignee->name : null,
        ];
    }
}
