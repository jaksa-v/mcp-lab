<?php

use function Pest\Laravel\postJson;

test('the web directory server rejects a request without a token', function () {
    postJson('/mcp/directory', mcpTicketsCall('search_people', ['query' => 'Sam']))
        ->assertUnauthorized();
});
