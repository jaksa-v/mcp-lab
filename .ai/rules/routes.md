---
paths:
  - routes/ai.php
---

# Routes

## Web tickets MCP uses Sanctum bearer tokens
Mcp::web('/mcp/tickets') and Mcp::web('/mcp/directory') use auth:sanctum and throttle:mcp. Do not wrap routes/ai.php in the web middleware group or CSRF will block Inspector. Inspector sends Authorization: Bearer with a Sanctum personal access token. The mcp limiter is defined in AppServiceProvider.

## Ticket tools talk to DirectoryServer through Client::local
Mcp::registerClient('directory') uses Client::local, not Client::web. php artisan serve is single-threaded; an HTTP self-call deadlocks. Tests override the named client with an in-process transport so SQLite :memory: is visible. One Pest test uses the real stdio client to list tools.
