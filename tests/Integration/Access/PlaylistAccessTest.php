<?php

namespace Tests\Integration\Access;

class PlaylistAccessTest extends BaseAccessTestCase
{
    public function test_all_playlist_routes_require_authentication(): void
    {
        $responses = [
            $this->client->request('POST', '/playlists', ['json' => ['title' => 'x']]),
            $this->client->request('GET', '/playlists'),
            $this->client->request('GET', '/playlists/1'),
            $this->client->request('PUT', '/playlists/1', ['json' => ['title' => 'x']]),
            $this->client->request('DELETE', '/playlists/1'),
        ];
        foreach ($responses as $response) {
            $this->assertSame(401, $response->getStatusCode());
        }
    }

    public function test_invalid_token_is_rejected_for_playlist_routes(): void
    {
        $response = $this->client->request('GET', '/playlists', ['headers' => ['Authorization' => 'Bearer invalid-token']]);
        $this->assertSame(401, $response->getStatusCode());
    }
}
