# mcp-lab

A Laravel app that exists to force every part of [Laravel MCP](https://laravel.com/docs/13.x/mcp). Fake helpdesk. Fake company. Throw it away when you are done.

This is the default reference for how MCP works in Laravel and how this repo uses it.

This is a gym, not a product. If you catch yourself picking a typeface or inventing a billing page, stop.

## How Laravel MCP works

[Model Context Protocol](https://modelcontextprotocol.io/docs/getting-started/intro) is JSON-RPC. An AI host lists what your server exposes, then calls it. Cursor and Inspector are the hosts this repo cares about. Laravel MCP, `laravel/mcp` 0.9.x here, is the wrapper.

You do not invent a protocol. You write PHP classes and register them.

### Servers

A server is a class that extends `Laravel\Mcp\Server`. It is a catalog of tools, resources, and prompts.

```php
#[Name('Northwind Tickets')]
#[Version('0.0.1')]
#[Instructions('...')]
class TicketsServer extends Server
{
    protected array $tools = [/* ... */];
    protected array $resources = [/* ... */];
    protected array $prompts = [/* ... */];
}
```

`#[Name]`, `#[Version]`, `#[Instructions]`, and `#[Icon]` are metadata the host shows the model. Instructions are the system prompt for that server. Keep them short and operational.

Create one with `php artisan make:mcp-server`. Register it in `routes/ai.php`. Laravel loads that file on its own. Do not add it to `bootstrap/app.php`.

### Local vs web

Same server class. Two ways in.

```php
Mcp::local('tickets', TicketsServer::class);
Mcp::web('/mcp/tickets', TicketsServer::class)->middleware(['auth:api', 'throttle:mcp']);
```

Local is stdio. The host runs `php artisan mcp:start tickets` as a child process. That is the daily Cursor loop. No HTTP session, no cookies, no Passport.

Web is HTTP JSON-RPC at that path. Inspector and remote hosts use it. Middleware applies, and this is where OAuth lives.

Do not wrap `routes/ai.php` in the `web` middleware group. CSRF will block Inspector.

### Tools, resources, and prompts

A server can expose tools, resources, and prompts. Generate them with `make:mcp-tool`, `make:mcp-resource`, and `make:mcp-prompt`. Then add the class to the server arrays. An unregistered class does nothing.

Tools are actions. The model calls them with arguments. `schema()` is the JSON Schema the host advertises. `handle(Request $request)` does the work. `$request->validate()` is ordinary Laravel validation. Write messages a model can act on, like `Give me the ticket id, like 12.`, not `The ticket_id field is required.`

Resources are readable documents at a URI. A static URI uses `#[Uri('desk://playbook')]`. Templates implement `HasUriTemplate` and read variables with `$request->get('id')`. MIME type uses `#[MimeType]`. The host can list them and read them without calling a tool.

Prompts are reusable message templates. `arguments()` declares what the host should collect. `handle()` returns messages, usually an assistant instruction plus a user message that includes real data. The model then writes from that.

Constructor-inject repositories. Do not put queries in the tool class. `handle()` can also type-hint Laravel services.

### Responses

`handle()` returns a `Laravel\Mcp\Response`, a response factory, an array of responses, or a `Generator`.

| Shape | How |
| --- | --- |
| Text | `Response::text('...')` |
| Error | `Response::error('Permission denied.')` |
| Structured JSON | `Response::structured($payload)` plus `outputSchema()` |
| Several text blocks | `Response::make([Response::text(...), Response::text(...)])` |
| Resource link | `Response::resourceLink(uri:, name:, mimeType:, title:)` |
| Blob from disk | `Response::fromStorage('badge.png')` |
| HTML app | `Response::view('mcp.queue-app', [...])` |
| Progress | `yield Response::notification('processing/progress', [...])` from a generator |

`Response::structured` cannot be empty and returns a factory. To mix structured JSON with resource links, build both and attach the payload with `withStructuredContent`. That is what `list_tickets` does.

Class `$meta` is metadata on the tool itself. `->withMeta([...])` is metadata on one response. `create_ticket` has the first. `get_ticket` has the second.

### Annotations

Hints for the host. They do not enforce anything in PHP. Policies still do.

| Attribute | Meaning here |
| --- | --- |
| `#[IsReadOnly]` | Does not write |
| `#[IsIdempotent]` | Safe to retry |
| `#[IsDestructive]` | Deletes or wrecks state |
| `#[IsOpenWorld]` | May touch the outside world. `who_is_on_call` wears this even though the rota is fake |
| `#[Priority]`, `#[Audience]`, `#[LastModified]` | Resource hints |
| `#[RendersApp]` | This tool opens an MCP App |

`shouldRegister(Request $request): bool` hides a tool, resource, or prompt from the list. If the host still tries to call a hidden one, the server returns not-found. Use it for role gates. `delete_ticket` is admin-only. Still check `$request->user()->can(...)` inside `handle()`. Listing and executing are different doors.

### Authorization

`$request->user()` is the signed-in user, same as a controller. Call `$user->can('update', $ticket)` and return `Response::error('Permission denied.')`. Do not invent a second auth system.

Local servers have no HTTP session. This app's ticket server signs in as Sam in `boot()` when `Auth` is empty. Web servers get the user from Passport.

### The MCP client

Laravel can call an MCP server too. Named clients live in a service provider.

```php
Mcp::registerClient('directory', fn () => Client::local('php', [
    'artisan', 'mcp:start', 'directory',
]));
```

Then ticket code does `Mcp::client('directory')->callTool('get_person', ['id' => $id])` or `->readResource('directory://people/'.$id)`.

`Client::local` spawns a process. `Client::web($url)` is HTTP. Same-app HTTP against `php artisan serve` deadlocks because that process is single-threaded. Use local for same-app calls.

### MCP Apps

An `AppResource` returns a self-contained HTML document at a `ui://` URI. A tool marked `#[RendersApp(resource: QueueApp::class)]` tells a capable host to fetch that HTML and put it in a sandboxed iframe.

The Blade view uses `<x-mcp::app>`. That component ships the client SDK. Inside the iframe, `createMcpApp` gives you `app.callServerTool(...)`. Vite and React do not apply. Tailwind and Alpine come from `#[AppMeta(libraries: [Library::Tailwind, Library::Alpine])]`.

`Visibility::App` hides a tool from the model so only the iframe can call it. `get_queue_data` is that tool.

Cursor lists these tools. It does not render the iframe. Pest is how you know the classes work.

### Auth on the web

The Laravel docs offer Sanctum and Passport. Sanctum is a bearer token. Passport is OAuth 2.1, which is what the protocol specifies.

This app uses Passport. `Mcp::oauthRoutes()` registers discovery and dynamic client registration. Web routes use `auth:api`. Laravel MCP advertises a single `mcp:use` scope. Publish `mcp-views` and point `Passport::authorizationView` at `resources/views/mcp/authorize.blade.php`. Leave that Blade alone.

### Testing

Inspector is for poking. Pest is how you know policies hold.

```php
TicketsServer::actingAs($sam)
    ->tool(ListTicketsTool::class, ['status' => 'open'])
    ->assertOk()
    ->assertSee('...');

TicketsServer::resource(TicketResource::class, ['id' => $ticket->id]);
TicketsServer::prompt(DraftReplyPrompt::class, ['ticket_id' => $ticket->id, 'tone' => 'curt']);
```

Template resources take the URI variables as the second argument. The helper expands `desk://tickets/{id}`.

`assertSee` only reads text and structured data. Resource links and `_meta` live on the raw JSON-RPC payload. This repo's `mcpRpc()` and `mcpToolContent()` in `tests/Helpers.php` read that. Generator tools use `assertSentNotification` and `assertNotificationCount`. The final result still holds the text payloads.

Web auth is an HTTP test. `POST /mcp/tickets` with `mcpTicketsCall()`. Unauthenticated requests must be 401, not a login redirect.

## What this repo is

Northwind Support. One Laravel 13 app, PHP 8.4, SQLite. Inertia and React are the dump page only. The agent is the write path.

Two MCP servers, each registered twice, local and web.

```
routes/ai.php

Mcp::local('directory', DirectoryServer::class);
Mcp::local('tickets', TicketsServer::class);

Mcp::oauthRoutes();

Mcp::web('/mcp/directory', DirectoryServer::class)
    ->middleware(['auth:api', 'throttle:mcp']);

Mcp::web('/mcp/tickets', TicketsServer::class)
    ->middleware(['auth:api', 'throttle:mcp']);
```

DirectoryServer is read-only people and teams. TicketsServer is the helpdesk. Ticket tools must not query `User` or `Team` through Eloquent. They look people up through the named `directory` client, `get_person` and `directory://people/{id}`. That is why there are two servers. If you `User::find()` from a ticket tool, you skipped the point.

Queries live in `DirectoryRepository` and `TicketRepository`. Tools inject those.

## Domain

Three roles on `users.role`.

| Role | What they can do |
| --- | --- |
| `requester` | Open tickets, comment on their own, read public KB |
| `agent` | See every ticket, assign, comment, change status, read internal KB |
| `admin` | Everything an agent can, plus delete tickets and the weekly-review prompt |

Policies are ordinary Laravel policies. `TicketPolicy` covers view, update, comment, and delete. `ArticlePolicy` hides internal articles from requesters. Staff is `Role::isStaff()`, agent or admin.

Login users come from `LabSeeder`. Password for all of them is `password`.

| Email | Role |
| --- | --- |
| `ada@northwind.test` | requester |
| `sam@northwind.test` | agent |
| `root@northwind.test` | admin |

`email_verified_at` is set. Fortify has verification enabled. `User` does not implement `MustVerifyEmail`, so you will not be blocked.

Jonah Hale, `jonah@northwind.test`, is the on-call agent.

### Tables

Keep them small. If a column is not needed by a tool, it is not there.

**users.** Starter-kit columns plus `role` (`requester\|agent\|admin`), `team_id` nullable, `title`, `on_call`.

**teams.** `name`, `slug`. Support, Billing, Warehouse.

**tickets.** `subject`, `body`, `status` (`open\|pending\|closed`), `priority` (`low\|normal\|high\|urgent`), `requester_id`, `assignee_id` nullable, `team_id` nullable.

**comments.** `ticket_id`, `user_id`, `body`.

**articles.** `slug`, `title`, `body`, `visibility` (`public\|internal`). Four rows. Public ones are refunds and shipping. Internal ones are escalation and refund-abuse.

`LabSeeder` runs from `DatabaseSeeder`. It is idempotent enough to re-run after `migrate:fresh`. Twenty tickets, thirty comments, eight people, one PNG at `storage/app/badge.png`.

## DirectoryServer

Read-only. The instructions say so. Name `Northwind Directory`, version `0.0.1`, teal icon.

### Tools

| Tool | What it does |
| --- | --- |
| `search_people` | Search by name, email, or title. Optional team slug. Structured `{ people }` with `outputSchema`. `#[IsReadOnly]` and `#[IsIdempotent]` |
| `get_person` | Look up one user id. Custom validation messages. Structured `{ person }` |
| `list_teams` | No required args. Two text blocks, names then slugs |
| `who_is_on_call` | Returns the `on_call` user. `#[IsOpenWorld]` on a fake rota so the annotation is used |

### Resources

| URI | What it does |
| --- | --- |
| `directory://org` | Static markdown. `#[MimeType]`, `#[Priority(0.9)]`, `#[Audience(Role::Assistant)]` |
| `directory://people/{id}` | Person dossier. `HasUriTemplate`, `$request->get('id')` |
| `directory://teams/{slug}` | Team dossier. Second template so the first is not a one-off |
| `directory://on-call` | Who is on call. `#[LastModified]` |
| `directory://badge` | Tiny PNG via `Response::fromStorage('badge.png')` |

No prompts here. They belong on the ticket server, where they have something to say.

## TicketsServer

Name `Northwind Tickets`, version `0.0.1`, dark icon.

`boot()` signs in as `sam@northwind.test` when nobody is authenticated. Local Cursor has no HTTP session. Without this, every tool would say `You must be signed in.` Web requests already have a Passport user, so `boot()` returns early.

### Tools

| Tool | What it does |
| --- | --- |
| `list_tickets` | Lists tickets the user can see. Status and priority filters. Structured output plus `desk://tickets/{id}` resource links |
| `get_ticket` | One ticket. Assignee and requester names come from `directory://people/{id}`. `withMeta(['source' => 'eloquent'])` |
| `create_ticket` | Opens a ticket. Class `$meta` has `version` and `author` |
| `add_comment` | Adds a comment after the `comment` policy check |
| `assign_ticket` | `#[IsIdempotent]`. Resolves the person with `Mcp::client('directory')->callTool('get_person', ...)` |
| `set_status` | Sets `open`, `pending`, or `closed`. This is close and reopen too |
| `delete_ticket` | `#[IsDestructive]`. `shouldRegister` is true only for admins |
| `close_stale_tickets` | Closes every `open` ticket older than N days. Yields `processing/progress` as it goes |
| `search_kb` | Searches articles the current user can see. Structured `{ articles }` with `desk://kb/{slug}` |
| `show_queue` | Model-visible. `#[RendersApp(resource: QueueApp::class)]` |
| `get_queue_data` | Same app, `visibility: [Visibility::App]`. The iframe refreshes without handing the model a second list tool |

`list_tickets` cannot use `TicketResource::uri()` for links. That method returns the template `desk://tickets/{id}`. Links must be the expanded string.

`assign_ticket` and `get_ticket` refuse to touch `User`. If the directory client is unplugged, assign fails. Pest proves that.

### Prompts

| Prompt | What it does |
| --- | --- |
| `draft_reply` | Takes `ticket_id` and `tone`. Loads the ticket. Assistant message plus a user message that includes the real subject |
| `triage_ticket` | Takes `ticket_id`. Validates with a useful error string |
| `weekly_review` | Recap of open tickets. `shouldRegister` is false for requesters |

### Resources

| URI | What it does |
| --- | --- |
| `desk://playbook` | Static markdown, high priority. How to triage |
| `desk://queue` | Markdown list of open tickets the current user can see |
| `desk://tickets/{id}` | Markdown dossier with comments |
| `desk://kb/{slug}` | One article. Missing and forbidden slugs return the same error |
| `desk://kb/escalation` | Staff-only listing of the internal escalation article |
| `ui://` QueueApp | Interactive queue iframe |

`desk://queue` is markdown. `QueueApp` is the iframe. Do not confuse them.

## QueueApp

`QueueApp` extends `AppResource`. Blade at `resources/views/mcp/queue-app.blade.php`. Alpine inside `<x-mcp::app>`. Refresh calls `get_queue_data` through `app.callServerTool`.

This is not an Inertia page. Do not rewrite it in React.

If the host cannot render MCP Apps, the classes and Pest tests are still the proof.

## Auth and the web servers

Both `/mcp/tickets` and `/mcp/directory` use `auth:api` and `throttle:mcp`. The `mcp` limiter is 60 per minute in `AppServiceProvider`, keyed by user id or IP.

`User` implements `OAuthenticatable` and uses Passport `HasApiTokens`. Missing the interface is the usual Passport setup bug.

An unauthenticated POST returns 401 with `WWW-Authenticate` pointing at that path's protected-resource metadata. Discovery covers both `/mcp/tickets` and `/mcp/directory`. Dynamic registration is `POST /oauth/register`. One access token works on both servers.

The approve and deny screen is `resources/views/mcp/authorize.blade.php`. Leave it.

## Dashboard

Fortify and Inertia come from the starter kit. Do not replace them.

`/dashboard` is two tables. Tickets show id, subject, status, priority, requester, and assignee. People show id, name, role, team, and on call. Wayfinder names the route. `DashboardController` passes Inertia props. No forms that create tickets.

Proof a write tool worked by refreshing `/dashboard`. Run `composer run dev` when you care about the React page.

Passport authorize and the QueueApp iframe stay Blade. The package owns those.

## How you talk to it

**Cursor, local.** `.cursor/mcp.json` starts both servers from the project root.

```json
{
    "mcpServers": {
        "northwind-tickets": {
            "command": "php",
            "args": ["artisan", "mcp:start", "tickets"]
        },
        "northwind-directory": {
            "command": "php",
            "args": ["artisan", "mcp:start", "directory"]
        }
    }
}
```

Tickets is the daily loop. You do not need directory in Cursor for the client work. The ticket server process spawns it.

Local tickets run as Sam. To see Ada or Root, use Pest `actingAs` or the web server with their token.

**Inspector.** `php artisan mcp:inspector tickets` and `php artisan mcp:inspector mcp/tickets`. Use this when a tool does nothing and you need the raw result. Web Inspector needs a Passport bearer token. The Inspector UI is on `:6274`, this app is on `:8000`. CORS for `mcp/*`, `oauth/*`, and `.well-known/*` lives in `config/cors.php`. Without those paths the discovery GETs return 200 and the browser still throws them away.

**Dashboard.** Proof the write tools hit Eloquent.

**Pest.** `php artisan test --compact tests/Feature/Mcp`.

Do not `Client::web('http://127.0.0.1:8000/mcp/directory')` from a ticket tool while `artisan serve` is the only PHP process. It will hang. `composer run dev` does not change that. A second PHP server on `:8001` plus `Client::web` is an optional later experiment, not the default.

## Testing this repo

Feature tests live in `tests/Feature/Mcp`. `TestCase` always rebinds the `directory` client to `InProcessDirectoryTransport` so SQLite `:memory:` is visible. One test in `DirectoryClientTest` uses the real stdio client to list tools. The unplugged-client test points the named client at `php -r 'exit(1);'`.

Helpers in `tests/Helpers.php`:

- `mcpRpc($response)` for the raw JSON-RPC array
- `mcpToolContent($response)` for the result content list
- `mcpTicketsCall($name, $arguments)` for a `tools/call` HTTP body

To assert a tool is hidden, `TicketsServer::actingAs($user)` then `(new TicketsServer(new FakeTransporter))->createContext()->tools()`. Do not call `handle()` on a hidden tool and expect a policy error. Invoking it is a not-found JSON-RPC error.

Read annotations from `$tool->annotations()`, not `toArray()['annotations']`. `Tool::toArray()` types that field as `array|object`. Resource `toArray()` omits annotations.

Freeze time with `Carbon::setTestNow`. Without `pest-plugin-phpstan`, `$this` is `TestCall` and `$this->travelTo` does not typecheck.

Import `Pest\Laravel\postJson` and `Pest\Laravel\withToken`. Do not call them on `$this`.

What the suite covers:

- Requester cannot assign or delete
- Agent can assign, cannot delete
- Admin can delete
- `delete_ticket` absent when acting as Sam, present for Root
- Internal escalation resource hidden from Ada
- `search_kb` hides internal articles from requesters
- `weekly_review` staff-only
- `assign_ticket` fails if the directory client cannot find the person
- Validation errors are sentences
- `close_stale_tickets` yields progress notifications
- OAuth discovery, registration, authorize Blade, PKCE, then a tool call on both web servers

Starter-kit Fortify tests stay. Do not rewrite them to prove MCP.

## File layout

```
app/
  Enums/Role.php Status.php Priority.php Visibility.php
  Models/User.php Team.php Ticket.php Comment.php Article.php
  Policies/TicketPolicy.php ArticlePolicy.php
  Repositories/DirectoryRepository.php TicketRepository.php ArticleRepository.php
  Http/Controllers/DashboardController.php
  Mcp/
    Servers/DirectoryServer.php TicketsServer.php
    Tools/          (15 tools)
    Resources/      (11 resources, including QueueApp)
    Prompts/        (3 prompts)
routes/ai.php
resources/js/pages/dashboard.tsx
resources/views/mcp/authorize.blade.php
resources/views/mcp/queue-app.blade.php
database/seeders/LabSeeder.php
tests/Feature/Mcp/
tests/Helpers.php
tests/Support/InProcessDirectoryTransport.php
.ai/rules/          (settled decisions for the next agent)
```

## Standing traps

These are already in `.ai/rules`. They belong here too because they are easy to re-break.

- Ticket tools talk to DirectoryServer through `Client::local`. Tests override that with the in-process transport.
- Do not query `User` or `Team` from `assign_ticket` or `get_ticket`. Do not move that lookup into `TicketRepository`.
- PHPDoc directory `$resources` as `class-string<Server\Resource>`. Pint lowercases `class-string<Resource>` to PHP's `resource` type.
- `ai.php` stays out of the web middleware group.
- QueueApp is `ui://`. `desk://queue` is markdown.
- KB articles go through `ArticleRepository`. Missing and forbidden `desk://kb/{slug}` reads use the same error.

## Docs

- Laravel MCP. https://laravel.com/docs/13.x/mcp
- Package. https://github.com/laravel/mcp
- Protocol. https://modelcontextprotocol.io/docs/getting-started/intro
- MCP Apps. https://modelcontextprotocol.io/extensions/apps/overview
- Inspector. https://modelcontextprotocol.io/docs/tools/inspector
