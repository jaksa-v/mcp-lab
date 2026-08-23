---
paths:
  - 'app/Mcp/Prompts/**'
---

# Prompts

## Ticket prompts load through the repository
draft_reply and triage_ticket inject TicketRepository, check view, and return Response::text(...)->asAssistant() plus a user message that includes the real ticket subject. weekly_review is staff-only via shouldRegister.
