---
paths:
  - config/cors.php
---

# Config

## Inspector OAuth needs CORS on MCP paths
MCP Inspector runs in the browser on :6274. Allow localhost and 127.0.0.1 on mcp/*, oauth/*, and .well-known/*. Expose WWW-Authenticate. Do not add a fake /token or openid-configuration route. Inspector guesses those only after CORS hides the real discovery document that points at /oauth/token.
