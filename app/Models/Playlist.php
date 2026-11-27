<?php

namespace App\Models;

use Core\Database\ActiveRecord\Model;
use Core\Database\Database;
use PDO;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $created_at
 * @property string $updated_at
 */
class Playlist extends Model
{
    protected static string $table = 'playlists';

    protected static array $columns = [
        'user_id',
        'title',
        'created_at',
        'updated_at',
    ];

    /** @return array<string, mixed>|null */
    public static function findOwned(int $playlistId, int $userId, ?PDO $pdo = null, bool $forUpdate = false): ?array
    {
        $pdo ??= Database::getDatabaseConn();
        $sql = 'SELECT id, title, created_at, updated_at FROM playlists WHERE id = :id AND user_id = :user_id';
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $playlistId, ':user_id' => $userId]);
        $playlist = $stmt->fetch(PDO::FETCH_ASSOC);

        return $playlist === false ? null : $playlist;
    }

    public static function countForUser(int $userId): int
    {
        $stmt = Database::getDatabaseConn()->prepare('SELECT COUNT(*) FROM playlists WHERE user_id = :user_id');
        $stmt->execute([':user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    /** @return array<int, array<string, mixed>> */
    public static function listForUser(int $userId, int $limit, int $offset): array
    {
        $stmt = Database::getDatabaseConn()->prepare(
            'SELECT id, title, created_at, updated_at FROM playlists WHERE user_id = :user_id '
            . 'ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $playlists = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($playlists === []) {
            return $playlists;
        }

        $covers = self::coverMoviesForPlaylists(array_map(static fn (array $playlist): int => (int) $playlist['id'], $playlists));
        foreach ($playlists as &$playlist) {
            $playlist['cover_movies'] = $covers[(int) $playlist['id']] ?? [];
        }
        unset($playlist);

        return $playlists;
    }

    /** @param array<int, int> $playlistIds
     * @return array<int, array<int, array{movie_id: int, poster_path: mixed}>>
     */
    private static function coverMoviesForPlaylists(array $playlistIds): array
    {
        $params = [];
        $placeholders = [];
        foreach ($playlistIds as $index => $playlistId) {
            $placeholder = ':playlist_id_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $playlistId;
        }

        $sql = 'SELECT playlist_movies.playlist_id, movies.id AS movie_id, movies.poster_path '
            . 'FROM playlist_movies INNER JOIN movies ON movies.id = playlist_movies.movie_id '
            . 'WHERE playlist_movies.playlist_id IN (' . implode(', ', $placeholders) . ') '
            . 'AND (SELECT COUNT(*) FROM playlist_movies AS earlier '
            . 'WHERE earlier.playlist_id = playlist_movies.playlist_id '
            . 'AND (earlier.created_at < playlist_movies.created_at '
            . 'OR (earlier.created_at = playlist_movies.created_at AND earlier.id <= playlist_movies.id))) <= 4 '
            . 'ORDER BY playlist_movies.playlist_id ASC, playlist_movies.created_at ASC, playlist_movies.id ASC';
        $stmt = Database::getDatabaseConn()->prepare($sql);
        foreach ($params as $placeholder => $playlistId) {
            $stmt->bindValue($placeholder, $playlistId, PDO::PARAM_INT);
        }
        $stmt->execute();

        $covers = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $movie) {
            $playlistId = (int) $movie['playlist_id'];
            $covers[$playlistId][] = [
                'movie_id' => (int) $movie['movie_id'],
                'poster_path' => $movie['poster_path'],
            ];
        }

        return $covers;
    }

    public static function movieCount(int $playlistId): int
    {
        $stmt = Database::getDatabaseConn()->prepare('SELECT COUNT(*) FROM playlist_movies WHERE playlist_id = :playlist_id');
        $stmt->execute([':playlist_id' => $playlistId]);
        return (int) $stmt->fetchColumn();
    }

    /** @return array<int, array<string, mixed>> */
    public static function movies(int $playlistId, int $limit, int $offset): array
    {
        $sql = 'SELECT movies.id AS movie_id, movies.title, movies.poster_path, movies.release_date '
            . 'FROM playlist_movies INNER JOIN movies ON movies.id = playlist_movies.movie_id '
            . 'WHERE playlist_movies.playlist_id = :playlist_id '
            . 'ORDER BY playlist_movies.created_at ASC, playlist_movies.id ASC LIMIT :limit OFFSET :offset';
        $stmt = Database::getDatabaseConn()->prepare($sql);
        $stmt->bindValue(':playlist_id', $playlistId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed> */
    public static function createInTransaction(PDO $pdo, int $userId, string $title): array
    {
        $stmt = $pdo->prepare('INSERT INTO playlists (user_id, title) VALUES (:user_id, :title)');
        $stmt->execute([':user_id' => $userId, ':title' => $title]);
        $playlistId = (int) $pdo->lastInsertId();
        $playlist = self::findOwned($playlistId, $userId, $pdo);
        if ($playlist === null) {
            throw new \RuntimeException('Playlist não criada');
        }
        return $playlist;
    }

    public static function updateTitle(PDO $pdo, int $playlistId, int $userId, string $title): void
    {
        $stmt = $pdo->prepare('UPDATE playlists SET title = :title WHERE id = :id AND user_id = :user_id');
        $stmt->execute([':title' => $title, ':id' => $playlistId, ':user_id' => $userId]);
    }

    /** @param array<int, int> $movieIds */
    public static function replaceMovies(PDO $pdo, int $playlistId, array $movieIds): bool
    {
        $stmt = $pdo->prepare('SELECT movie_id FROM playlist_movies WHERE playlist_id = :playlist_id');
        $stmt->execute([':playlist_id' => $playlistId]);
        $existing = array_fill_keys(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)), true);
        $requested = array_fill_keys($movieIds, true);
        $changed = false;

        $delete = $pdo->prepare('DELETE FROM playlist_movies WHERE playlist_id = :playlist_id AND movie_id = :movie_id');
        foreach ($existing as $movieId => $_) {
            if (!isset($requested[$movieId])) {
                $delete->execute([':playlist_id' => $playlistId, ':movie_id' => $movieId]);
                $changed = true;
            }
        }

        $insert = $pdo->prepare('INSERT INTO playlist_movies (playlist_id, movie_id) VALUES (:playlist_id, :movie_id)');
        foreach ($movieIds as $movieId) {
            if (!isset($existing[$movieId])) {
                $insert->execute([':playlist_id' => $playlistId, ':movie_id' => $movieId]);
                $changed = true;
            }
        }

        return $changed;
    }

    public static function touch(PDO $pdo, int $playlistId, int $userId): void
    {
        $stmt = $pdo->prepare('UPDATE playlists SET updated_at = CURRENT_TIMESTAMP WHERE id = :id AND user_id = :user_id');
        $stmt->execute([':id' => $playlistId, ':user_id' => $userId]);
    }

    public static function deleteOwned(PDO $pdo, int $playlistId, int $userId): void
    {
        $stmt = $pdo->prepare('DELETE FROM playlists WHERE id = :id AND user_id = :user_id');
        $stmt->execute([':id' => $playlistId, ':user_id' => $userId]);
    }
}
