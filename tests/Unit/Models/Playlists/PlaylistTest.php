<?php

namespace Tests\Unit\Models\Playlists;

use App\Models\Movie;
use App\Models\Playlist;
use App\Models\User;
use Core\Database\Database;
use PDOException;
use Tests\TestCase;

class PlaylistTest extends TestCase
{
    private User $owner;
    private User $other;

    public function setUp(): void
    {
        parent::setUp();
        $this->owner = $this->user('playlist-owner');
        $this->other = $this->user('playlist-other');
        foreach ([101, 102, 103] as $id) {
            Movie::saveFromTmdb(['id' => $id, 'title' => "Movie {$id}"]);
        }
    }

    public function test_persists_owned_playlists_with_duplicate_titles_and_orders_by_creation_then_id(): void
    {
        $pdo = Database::getDatabaseConn();
        $first = Playlist::createInTransaction($pdo, $this->owner->id, 'Repetida');
        $second = Playlist::createInTransaction($pdo, $this->owner->id, 'Repetida');
        $third = Playlist::createInTransaction($pdo, $this->other->id, 'Outra');
        $pdo->exec("UPDATE playlists SET created_at = '2020-01-01 00:00:00' WHERE user_id = {$this->owner->id}");

        $this->assertSame(2, Playlist::countForUser($this->owner->id));
        $this->assertSame([$second['id'], $first['id']], array_column(Playlist::listForUser($this->owner->id, 10, 0), 'id'));
        $this->assertNull(Playlist::findOwned((int) $third['id'], $this->owner->id));
    }

    public function test_replaces_movies_without_reordering_kept_movies_and_enforces_unique_pivot(): void
    {
        $pdo = Database::getDatabaseConn();
        $playlist = Playlist::createInTransaction($pdo, $this->owner->id, 'Lista');
        Playlist::replaceMovies($pdo, (int) $playlist['id'], [101, 102]);
        Playlist::replaceMovies($pdo, (int) $playlist['id'], [102, 103]);

        $this->assertSame([102, 103], array_column(Playlist::movies((int) $playlist['id'], 10, 0), 'movie_id'));
        $this->expectException(PDOException::class);
        $pdo->prepare('INSERT INTO playlist_movies (playlist_id, movie_id) VALUES (:playlist_id, :movie_id)')
            ->execute([':playlist_id' => $playlist['id'], ':movie_id' => 102]);
    }

    public function test_cascades_playlist_user_and_movie_deletions_without_deleting_shared_movie(): void
    {
        $pdo = Database::getDatabaseConn();
        $one = Playlist::createInTransaction($pdo, $this->owner->id, 'Um');
        $two = Playlist::createInTransaction($pdo, $this->owner->id, 'Dois');
        Playlist::replaceMovies($pdo, (int) $one['id'], [101, 102]);
        Playlist::replaceMovies($pdo, (int) $two['id'], [101]);
        $otherPlaylist = Playlist::createInTransaction($pdo, $this->other->id, 'Terceira');
        Playlist::replaceMovies($pdo, (int) $otherPlaylist['id'], [102]);
        $pdo->prepare('DELETE FROM playlists WHERE id = :id')->execute([':id' => $one['id']]);
        $this->assertSame(1, Playlist::movieCount((int) $two['id']));
        $this->assertSame(1, (int) $pdo->query('SELECT COUNT(*) FROM movies WHERE id = 101')->fetchColumn());
        $pdo->prepare('DELETE FROM movies WHERE id = :id')->execute([':id' => 101]);
        $this->assertSame(0, Playlist::movieCount((int) $two['id']));
        $this->other->destroy();
        $this->assertNull(Playlist::findOwned((int) $otherPlaylist['id'], $this->other->id));
        $this->assertSame(1, Playlist::countForUser($this->owner->id));
    }

    public function test_rolls_back_a_failed_multi_statement_playlist_transaction(): void
    {
        $pdo = Database::getDatabaseConn();
        $pdo->beginTransaction();
        try {
            $playlist = Playlist::createInTransaction($pdo, $this->owner->id, 'Atômica');
            Playlist::replaceMovies($pdo, (int) $playlist['id'], [101]);
            $pdo->prepare('INSERT INTO playlist_movies (playlist_id, movie_id) VALUES (:playlist_id, :movie_id)')
                ->execute([':playlist_id' => $playlist['id'], ':movie_id' => 101]);
            $pdo->commit();
            $this->fail('A restrição unique deveria interromper a transação');
        } catch (PDOException) {
            $pdo->rollBack();
        }
        $this->assertSame(0, Playlist::countForUser($this->owner->id));
    }

    private function user(string $handle): User
    {
        $user = new User(['username' => $handle, 'handle' => $handle, 'email' => $handle . '@example.test', 'password' => 'password123', 'role' => 'Default']);
        $user->save();
        return $user;
    }
}
