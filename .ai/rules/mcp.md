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
