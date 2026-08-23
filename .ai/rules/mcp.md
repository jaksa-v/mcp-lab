---
paths:
  - 'tests/Feature/Mcp/**'
---

# Mcp

## URI template tests pass template variables
For HasUriTemplate resources, call TicketsServer::resource(TicketResource::class, ['id' => $ticket->id]). The test helper expands desk://tickets/{id} from those keys. Static resources take no arguments. List static URIs via $server->createContext()->resources(); templates live in resourceTemplates().
