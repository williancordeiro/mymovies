<?php

namespace Tests\Integration\Access;

use Database\Populate\UsersPopulate;

class MovieSearchAccessTest extends BaseAccessTestCase
{
    public function test_search_route_should_be_accessible_without_authentication(): void
    {
        $response = $this->client->request('GET', '/movies/search?q=bat');

        $this->assertNotEquals(401, $response->getStatusCode());
    }

    public function test_search_route_should_be_accessible_with_authentication(): void
    {
        $loginResponse = $this->client->request('POST', '/auth/login', [
        'json' => ['email' => 'example@email.com', 'password' => 'password123']
        ]);

        $body = json_decode((string) $loginResponse->getBody(), true);

    // Captura o token de forma segura (se não existir, usa uma string vazia)
        $token = $body['token'] ?? '';

        $response = $this->client->request('GET', '/movies/search?q=bat', [
        'headers' => ['Authorization' => 'Bearer ' . $token]
        ]);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_search_route_should_reject_short_query_regardless_of_authentication(): void
    {
        $response = $this->client->request('GET', '/movies/search?q=ba');

        $this->assertEquals(400, $response->getStatusCode());
    }
}
