---
paths:
  - app/Mcp/Servers/DirectoryServer.php
---

# Servers

## Directory primitives query through DirectoryRepository
Directory tools and resources inject DirectoryRepository. Do not query User or Team in those classes. Keep the server instructions marked read-only. PHPDoc the resources list as class-string<Server\Resource>; Pint lowercases class-string<Resource> to PHP's resource type.
