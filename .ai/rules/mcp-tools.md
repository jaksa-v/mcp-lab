---
paths:
  - 'app/Mcp/{Tools/SearchKbTool.php,Resources/ArticleResource.php,Resources/EscalationResource.php}'
---

# Mcp Tools

## KB articles go through ArticleRepository
search_kb and desk://kb/{slug} query through ArticleRepository. Requesters only see public articles. Missing or forbidden slugs return the same error so internal titles stay hidden. desk://kb/escalation stays as a staff-only listed shortcut.
