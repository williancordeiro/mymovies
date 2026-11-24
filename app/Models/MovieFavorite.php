<?php

namespace App\Models;

use Core\Database\ActiveRecord\BelongsTo;
use Core\Database\ActiveRecord\Model;
use Core\Database\Database;
use PDO;

/**
 * @property int $id
 * @property int $user_id
 * @property int $movie_id
 * @property string $created_at
 */
class MovieFavorite extends Model
{
    protected static string $table = 'movies_favorites';

    protected static array $columns = [
        'user_id',
        'movie_id',
        'created_at',
    ];

    protected ?int $id = null;
    protected int $user_id;
    protected int $movie_id;
    protected ?string $created_at = null;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'movie_id');
    }

    /**
     * @param array<int, int> $movieIds
     * @return array<int, bool>
     */
    public static function favoritedMovieIdsForUser(int $userId, array $movieIds): array
    {
        $movieIds = array_values(array_unique($movieIds));
        if ($movieIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($movieIds), '?'));
        $stmt = Database::getDatabaseConn()->prepare(
            "SELECT movie_id FROM movies_favorites WHERE user_id = ? AND movie_id IN ({$placeholders})"
        );
        $stmt->execute(array_merge([$userId], $movieIds));

        $favorites = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $movieId) {
            $favorites[(int) $movieId] = true;
        }

        return $favorites;
    }

    public static function countForUser(int $userId): int
    {
        $stmt = Database::getDatabaseConn()->prepare(
            'SELECT COUNT(*) FROM movies_favorites WHERE user_id = :user_id'
        );
        $stmt->execute([':user_id' => $userId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function listForUser(int $userId, int $limit, int $offset): array
    {
        $sql = <<<'SQL'
            SELECT movies.id AS movie_id, movies.title, movies.poster_path, movies.release_date
            FROM movies_favorites
            INNER JOIN movies ON movies.id = movies_favorites.movie_id
            WHERE movies_favorites.user_id = :user_id
            ORDER BY movies_favorites.created_at DESC, movies_favorites.id DESC
            LIMIT :limit OFFSET :offset
        SQL;

        $stmt = Database::getDatabaseConn()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
