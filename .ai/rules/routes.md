---
paths:
  - routes/ai.php
---

# Routes

## Both web MCP servers use Passport
Both web MCP servers use auth:api and throttle:mcp. Mcp::oauthRoutes() is shared. Do not wrap ai.php in the web middleware group or CSRF will block Inspector. Passport::authorizationView uses resources/views/mcp/authorize.blade.php — leave that Blade as-is. User must implement OAuthenticatable and use Passport HasApiTokens. The mcp limiter is defined in AppServiceProvider.

## Ticket tools talk to DirectoryServer through Client::local
Mcp::registerClient('directory') uses Client::local, not Client::web. php artisan serve is single-threaded; an HTTP self-call deadlocks. Tests override the named client with an in-process transport so SQLite :memory: is visible. One Pest test uses the real stdio client to list tools.
