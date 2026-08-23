---
paths:
  - app/Mcp/Tools/AssignTicketTool.php
---

# Tools

## assign_ticket still uses Eloquent for people
Phase 3 looks up the assignee with User::query()->find() on purpose. Phase 8 replaces that call with the directory MCP client. Do not move the lookup into TicketRepository, and do not User::find() from other ticket tools.
