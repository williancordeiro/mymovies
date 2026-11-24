<?php

namespace Tests\Unit\Models\Movies;

use App\Models\Movie;
use App\Models\MovieFavorite;
use App\Models\User;
use Core\Database\Database;
use PDOException;
use Tests\TestCase;

class MovieFavoriteTest extends TestCase
{
    private User $user;
    private User $otherUser;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser('favorite-user');
        $this->otherUser = $this->createUser('other-favorite-user');
        $this->saveMovie(1001, 'Primeiro filme');
        $this->saveMovie(1002, 'Segundo filme');
        $this->saveMovie(1003, 'Terceiro filme');
    }

    public function test_should_enforce_one_favorite_per_user_and_movie(): void
    {
        $this->createFavorite($this->user->id, 1001);

        $this->expectException(PDOException::class);
        $this->createFavorite($this->user->id, 1001);
    }

    public function test_should_keep_favorites_isolated_and_return_only_requested_ids(): void
    {
        $this->createFavorite($this->user->id, 1001);
        $this->createFavorite($this->otherUser->id, 1002);

        $this->assertSame([1001 => true], MovieFavorite::favoritedMovieIdsForUser($this->user->id, [1001, 1002, 1001]));
        $this->assertSame([], MovieFavorite::favoritedMovieIdsForUser($this->user->id, []));
        $this->assertSame(1, MovieFavorite::countForUser($this->user->id));
    }

    public function test_should_list_favorites_by_creation_then_id_descending_with_pagination(): void
    {
        $this->createFavorite($this->user->id, 1001);
        $this->createFavorite($this->user->id, 1002);
        $this->createFavorite($this->user->id, 1003);

        $firstPage = MovieFavorite::listForUser($this->user->id, 2, 0);
        $secondPage = MovieFavorite::listForUser($this->user->id, 2, 2);

        $this->assertSame([1003, 1002], array_column($firstPage, 'movie_id'));
        $this->assertSame([1001], array_column($secondPage, 'movie_id'));
        $this->assertSame([], MovieFavorite::listForUser($this->user->id, 20, 20));
    }

    public function test_should_cascade_when_user_or_movie_is_deleted(): void
    {
        $this->createFavorite($this->user->id, 1001);
        $this->createFavorite($this->otherUser->id, 1002);

        $this->user->destroy();
        Database::getDatabaseConn()->prepare('DELETE FROM movies WHERE id = :id')->execute([':id' => 1002]);

        $this->assertSame(0, MovieFavorite::countForUser($this->user->id));
        $this->assertSame(0, MovieFavorite::countForUser($this->otherUser->id));
    }

    private function createUser(string $handle): User
    {
        $user = new User([
            'username' => $handle,
            'handle' => $handle,
            'email' => $handle . '@example.test',
            'password' => 'password123',
            'role' => 'Default',
        ]);
        $user->save();

        return $user;
    }

    private function saveMovie(int $id, string $title): void
    {
        Movie::saveFromTmdb([
            'id' => $id,
            'title' => $title,
            'overview' => 'Descrição',
            'poster_path' => '/poster.png',
            'release_date' => '2024-01-01',
            'vote_average' => 8.0,
        ]);
    }

    private function createFavorite(int $userId, int $movieId): MovieFavorite
    {
        $favorite = new MovieFavorite(['user_id' => $userId, 'movie_id' => $movieId]);
        $favorite->save();

        return $favorite;
    }
}
