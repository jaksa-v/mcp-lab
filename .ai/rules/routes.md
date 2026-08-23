---
paths:
  - routes/ai.php
---

# Routes

## Web tickets MCP uses Sanctum bearer tokens
Mcp::web('/mcp/tickets') uses auth:sanctum and throttle:mcp. Do not wrap routes/ai.php in the web middleware group or CSRF will block Inspector. Inspector sends Authorization: Bearer with a Sanctum personal access token. The mcp limiter is defined in AppServiceProvider.
