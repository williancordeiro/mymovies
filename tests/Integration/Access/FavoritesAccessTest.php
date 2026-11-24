<?php

namespace Tests\Integration\Access;

class FavoritesAccessTest extends BaseAccessTestCase
{
    public function test_favorite_routes_should_require_authentication(): void
    {
        $responses = [
            $this->client->request('POST', '/movies/favorites', ['json' => ['movie_id' => 1]]),
            $this->client->request('DELETE', '/movies/favorites/1'),
            $this->client->request('GET', '/favorites'),
        ];

        foreach ($responses as $response) {
            $this->assertSame(401, $response->getStatusCode());
        }
    }

    public function test_favorite_routes_should_reject_an_invalid_token(): void
    {
        $response = $this->client->request('GET', '/favorites', [
            'headers' => ['Authorization' => 'Bearer invalid-token'],
        ]);

        $this->assertSame(401, $response->getStatusCode());
    }
}
