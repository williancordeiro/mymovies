<?php

namespace Tests\Integration\Access;

use App\Models\Movie;

class MovieRatingAccessTest extends BaseAccessTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Movie::saveFromTmdb([
            'id' => 1226863,
            'title' => 'Filme de Teste',
            'overview' => 'Descrição',
            'poster_path' => '/poster.png',
            'release_date' => '2024-01-01',
            'vote_average' => 8.0
        ]);
    }
    public function test_movie_rating_routes_should_require_authentication(): void
    {
        $responseRate = $this->client->request('POST', '/movies/rate', [
            'json' => ['movie_id' => 1226863, 'rating' => 5]
        ]);
        $this->assertEquals(401, $responseRate->getStatusCode());

        $responseUnrate = $this->client->request('DELETE', '/movies/rate/1226863');
        $this->assertEquals(401, $responseUnrate->getStatusCode());
    }

    public function test_authenticated_user_should_access_movie_rating_routes(): void
    {
        $loginResponse = $this->client->request('POST', '/auth/login', [
            'json' => ['email' => 'example@email.com', 'password' => 'password123']
        ]);
        $token = json_decode((string) $loginResponse->getBody(), true)['token'];

        $responseRate = $this->client->request('POST', '/movies/rate', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'json' => ['movie_id' => 1226863, 'rating' => 5]
        ]);
        $this->assertEquals(200, $responseRate->getStatusCode());

        $responseUnrate = $this->client->request('DELETE', '/movies/rate/1226863', [
            'headers' => ['Authorization' => 'Bearer ' . $token]
        ]);
        $this->assertEquals(200, $responseUnrate->getStatusCode());
    }
}
