<?php

namespace Tests\Unit\Models\Movies;

use App\Models\User;
use App\Models\Movie;
use App\Models\MovieRating;
use Tests\TestCase;

class MovieRatingTest extends TestCase
{
    private User $user;
    private Movie $movie;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = new User([
            'username' => 'RaterUser',
            'email' => 'rater@email.com',
            'password' => '123456',
            'handle' => 'rateruser',
            'role' => 'Default'
        ]);
        $this->user->save();

        Movie::saveFromTmdb([
            'id' => 1226863,
            'title' => 'Filme Unitário',
            'overview' => 'Descrição',
            'poster_path' => '/poster.png',
            'release_date' => '2024-01-01',
            'vote_average' => 8.0
        ]);
        $this->movie = Movie::findById(1226863);
    }

    public function test_should_create_movie_rating(): void
    {
        $rating = new MovieRating([
            'user_id' => $this->user->id,
            'movie_id' => $this->movie->id,
            'rating' => 5
        ]);

        $this->assertTrue($rating->save());
    }

    public function test_should_not_save_with_invalid_rating(): void
    {
        $rating = new MovieRating([
        'user_id' => $this->user->id,
        'movie_id' => $this->movie->id,
        'rating' => 0  // fora do range válido (1-5)
        ]);

        $this->assertFalse($rating->save());
    }

    public function test_user_relationship_should_return_correct_user(): void
    {
        $rating = new MovieRating([
            'user_id' => $this->user->id,
            'movie_id' => $this->movie->id,
            'rating' => 4
        ]);
        $rating->save();

        $foundUser = $rating->user()->get();

        $this->assertEquals($this->user->id, $foundUser->id);
    }

    public function test_get_rating_for_user_id_should_return_rating(): void
    {
        $rating = new MovieRating([
            'user_id' => $this->user->id,
            'movie_id' => $this->movie->id,
            'rating' => 3
        ]);
        $rating->save();

        $found = MovieRating::getRatingForUserId($this->user->id);

        $this->assertNotNull($found);
        $this->assertEquals(3, $found->rating);
    }

    public function test_get_average_by_movie_id_should_calculate_correctly(): void
    {
        $user2 = new User([
            'username' => 'RaterUser2',
            'email' => 'rater2@email.com',
            'password' => '123456',
            'handle' => 'rateruser2',
            'role' => 'Default'
        ]);
        $user2->save();

        (new MovieRating([
            'user_id' => $this->user->id,
            'movie_id' => $this->movie->id,
            'rating' => 4
        ]))->save();

        (new MovieRating([
            'user_id' => $user2->id,
            'movie_id' => $this->movie->id,
            'rating' => 2
        ]))->save();

        $average = MovieRating::getAverageByMovieId($this->movie->id);

        $this->assertEquals(3.0, $average);
    }

    public function test_get_average_by_movie_id_should_return_zero_when_no_ratings(): void
    {
        $average = MovieRating::getAverageByMovieId(9999999);
        $this->assertEquals(0.0, $average);
    }
}
