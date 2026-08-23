---
paths:
  - 'tests/Feature/Mcp/**'
---

# Mcp

## URI template tests pass template variables
For HasUriTemplate resources, call TicketsServer::resource(TicketResource::class, ['id' => $ticket->id]). The test helper expands desk://tickets/{id} from those keys. Static resources take no arguments. List static URIs via $server->createContext()->resources(); templates live in resourceTemplates().

## shouldRegister lists are filtered per actingAs user
To assert a primitive is hidden, TicketsServer::actingAs($user) then (new TicketsServer(new FakeTransporter))->createContext()->tools()/resources()/prompts(). Do not call handle() directly. Invoking a hidden primitive returns a not-found JSON-RPC error: delete_ticket is admin-only, weekly_review and desk://kb/escalation are staff-only.

## Split expect()->not after mixin matchers
Do not chain ->not onto toContain() (or other mixin matchers). PHPStan types the matcher return as Pest\Mixins\Expectation, which has no $not. Start a second expect($value)->not->toContain(...).

## Inspect resource links and content meta via mcpRpc
TestResponse::assertSee only reads content text/data. Resource links (type resource_link) and content _meta are invisible to it. Use mcpRpc($response) from tests/Pest.php to read the raw JSON-RPC result. Generator tools: assertSentNotification / assertNotificationCount for yielded notifications; the final result still holds the text payloads.

## Read tool content through mcpToolContent
Use mcpToolContent($response) for the result content list. It narrows the mixed JSON-RPC payload so collect() is unnecessary. Freeze time with Carbon::setTestNow, not $this->travelTo — without pest-plugin-phpstan, $this is typed as TestCall.

## Web MCP auth is an HTTP JSON-RPC test
Prove Sanctum on the web tickets server with POST /mcp/tickets, not TicketsServer::tool(). Import `Pest\Laravel\postJson` and `Pest\Laravel\withToken`; do not call them on `$this` (typed as TestCall without pest-plugin-phpstan). Use mcpTicketsCall() for the JSON-RPC body. Unauthenticated requests must assertUnauthorized (401), not a login redirect.
