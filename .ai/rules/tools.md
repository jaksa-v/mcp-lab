---
paths:
  - app/Mcp/Tools/AssignTicketTool.php
  - app/Mcp/Tools/GetTicketTool.php
  - app/Mcp/Tools/ListTicketsTool.php
---

# Tools

## assign_ticket and get_ticket look up people through the directory client
Do not User::query() or $ticket->assignee from those tools. assign_ticket calls Mcp::client('directory')->callTool('get_person'). get_ticket reads directory://people/{id}. If the client is unplugged, assign fails. Do not move the lookup into TicketRepository.

## list_tickets pairs structured output with resource links
Response::structured cannot be empty and returns a factory. TicketResource::uri() is the template desk://tickets/{id}, so resource links must use the expanded URI string. Combine JSON text from Response::structured with those links via Response::make(...)->withStructuredContent($payload).
