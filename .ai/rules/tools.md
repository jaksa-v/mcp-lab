---
paths:
  - app/Mcp/Tools/AssignTicketTool.php
  - app/Mcp/Tools/ListTicketsTool.php
---

# Tools

## assign_ticket still uses Eloquent for people
Phase 3 looks up the assignee with User::query()->find() on purpose. Phase 8 replaces that call with the directory MCP client. Do not move the lookup into TicketRepository, and do not User::find() from other ticket tools.

## list_tickets pairs structured output with resource links
Response::structured cannot be empty and returns a factory. TicketResource::uri() is the template desk://tickets/{id}, so resource links must use the expanded URI string. Combine JSON text from Response::structured with those links via Response::make(...)->withStructuredContent($payload).
