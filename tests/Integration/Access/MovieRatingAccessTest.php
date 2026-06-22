<?php

namespace Tests\Integration\Access;

use App\Models\Movie;
use Database\Populate\MoviesPopulate;

class MovieRatingAccessTest extends BaseAccessTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        MoviesPopulate::populate();
    }

    public function test_movie_rating_routes_should_require_authentication(): void
    {
        $responseRate = $this->client->request('POST', '/movies/rate', [
            'json' => ['movie_id' => 1275779, 'rating' => 5]
        ]);
        $this->assertEquals(401, $responseRate->getStatusCode());

        $responseUnrate = $this->client->request('DELETE', '/movies/rate/1226863');
        $this->assertEquals(401, $responseUnrate->getStatusCode());
    }
}
