<?php

namespace App\Mcp\Resources;

use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Annotations\Priority;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Name('playbook')]
#[Title('Triage playbook')]
#[Description('How Northwind Support triages tickets: priority, teams, and who can do what.')]
#[Uri('desk://playbook')]
#[MimeType('text/markdown')]
#[Priority(0.9)]
class PlaybookResource extends Resource
{
    public function handle(): Response
    {
        return Response::text(<<<'MARKDOWN'
# Northwind Support playbook

Triage every ticket before you assign it.

## How to triage

1. Read the subject and the body. Do not guess from the subject alone.
2. Set priority. Urgent if money, safety, or a blocked warehouse is on the line. High if a customer is waiting. Otherwise normal or low.
3. Assign a team. Billing owns refunds and invoices. Warehouse owns shipping and pallets. Support owns everything else.
4. Leave the ticket open while you work. Pending if you are waiting on the customer. Closed only when the requester is done.

## Who can do what

- Requesters open tickets and comment on their own.
- Agents see every ticket, assign, comment, and change status.
- Admins can also delete tickets.

## Where to look

- Open queue: `desk://queue`
- One ticket: `desk://tickets/{id}`
- Tools: `list_tickets`, `get_ticket`
MARKDOWN);
    }
}
