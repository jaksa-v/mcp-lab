<?php

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withToken;

test('oauth discovery advertises both web servers and mcp scope', function () {
    getJson('/.well-known/oauth-authorization-server')
        ->assertOk()
        ->assertJsonPath('authorization_endpoint', route('passport.authorizations.authorize'))
        ->assertJsonPath('token_endpoint', route('passport.token'))
        ->assertJsonPath('registration_endpoint', url('/oauth/register'))
        ->assertJsonPath('scopes_supported.0', 'mcp:use')
        ->assertJsonPath('code_challenge_methods_supported.0', 'S256');

    getJson('/.well-known/oauth-protected-resource/mcp/tickets')
        ->assertOk()
        ->assertJsonPath('resource', url('/mcp/tickets'))
        ->assertJsonPath('scopes_supported.0', 'mcp:use');

    getJson('/.well-known/oauth-protected-resource/mcp/directory')
        ->assertOk()
        ->assertJsonPath('resource', url('/mcp/directory'))
        ->assertJsonPath('scopes_supported.0', 'mcp:use');
});

test('dynamic registration creates a public mcp client', function () {
    postJson('/oauth/register', [
        'client_name' => 'Inspector',
        'redirect_uris' => ['http://127.0.0.1/oauth/callback'],
    ])
        ->assertCreated()
        ->assertJsonPath('token_endpoint_auth_method', 'none')
        ->assertJsonPath('scope', 'mcp:use')
        ->assertJsonPath('grant_types.0', 'authorization_code');
});

test('guests are sent to login before they can authorize an mcp client', function () {
    $client = registerNorthwindOauthClient();

    get(oauthAuthorizeUrl($client['client_id'], $client['redirect_uri']))
        ->assertRedirect(route('login'));
});

test('sam sees the published mcp authorize screen', function () {
    $sam = User::factory()->agent()->create([
        'email' => 'sam@northwind.test',
        'name' => 'Sam Carter',
    ]);
    $client = registerNorthwindOauthClient();

    actingAs($sam)
        ->get(oauthAuthorizeUrl($client['client_id'], $client['redirect_uri']))
        ->assertOk()
        ->assertSee('Authorize Inspector')
        ->assertSee('sam@northwind.test')
        ->assertSee('Use MCP server');
});

test('sam can finish the authorize and token dance then call both web servers', function () {
    $sam = User::factory()->agent()->create([
        'email' => 'sam@northwind.test',
        'name' => 'Sam Carter',
    ]);
    Ticket::factory()->open()->create([
        'subject' => 'Warehouse door is jammed',
    ]);

    $client = registerNorthwindOauthClient();
    [$verifier, $challenge] = pkcePair();

    actingAs($sam)
        ->get(oauthAuthorizeUrl($client['client_id'], $client['redirect_uri'], $challenge))
        ->assertOk();

    $authToken = session('authToken');
    expect($authToken)->toBeString();

    $approved = actingAs($sam)->post(route('passport.authorizations.approve'), [
        'client_id' => $client['client_id'],
        'auth_token' => $authToken,
        'state' => '',
    ]);

    $approved->assertRedirect();

    $location = (string) $approved->headers->get('Location');
    parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

    expect($query['code'] ?? null)->toBeString();
    expect($query['code'])->not->toBe('');

    $token = postJson('/oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => $client['client_id'],
        'redirect_uri' => $client['redirect_uri'],
        'code' => $query['code'],
        'code_verifier' => $verifier,
    ])
        ->assertOk()
        ->json('access_token');

    expect($token)->toBeString();

    withToken($token)
        ->postJson('/mcp/tickets', mcpTicketsCall())
        ->assertOk()
        ->assertJsonPath('result.structuredContent.tickets.0.subject', 'Warehouse door is jammed');

    withToken($token)
        ->postJson('/mcp/directory', mcpTicketsCall('search_people', ['query' => 'Sam']))
        ->assertOk()
        ->assertJsonPath('result.structuredContent.people.0.name', 'Sam Carter');
});

/**
 * @return array{client_id: string, redirect_uri: string}
 */
function registerNorthwindOauthClient(string $redirectUri = 'http://127.0.0.1/oauth/callback'): array
{
    $response = postJson('/oauth/register', [
        'client_name' => 'Inspector',
        'redirect_uris' => [$redirectUri],
    ])->assertCreated();

    $clientId = $response->json('client_id');

    expect($clientId)->toBeString();

    return [
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
    ];
}

function oauthAuthorizeUrl(string $clientId, string $redirectUri, ?string $challenge = null): string
{
    $challenge ??= pkcePair()[1];

    return '/oauth/authorize?'.http_build_query([
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'scope' => 'mcp:use',
        'code_challenge' => $challenge,
        'code_challenge_method' => 'S256',
        'state' => Str::random(16),
    ]);
}

/**
 * @return array{0: string, 1: string}
 */
function pkcePair(): array
{
    $verifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

    return [$verifier, $challenge];
}
