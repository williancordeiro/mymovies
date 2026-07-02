<?php

namespace App\Models;

use Core\Database\ActiveRecord\Model;
use Lib\Validations;
use Core\Database\ActiveRecord\HasMany;
use Core\Database\ActiveRecord\BelongsTo;
use Core\Database\Database;
use PDO;

/**
 * @property int $id
 * @property int $user_id
 * @property int $movie_id
 * @property int $rating
 * @property string $created_at
 * @property string $updated_at
 */

class MovieRatingTag extends Model
{
    protected static string $table = 'movies_rating_tags';

    protected static array $columns = [
        'movie_rating__id',
        'rating_tag_id',
        'created_at',
        'updated_at'
    ];

    protected ?int $id = null;
    protected int $movie_rating_id;
    protected int $rating_tag_id;
    protected ?string $created_at = null;
    protected ?string $updated_at = null;

    
    public function movieRating(): BelongsTo
    {
        return $this->belongsTo(movieRating::class, 'movie_rating_id');
    }


    public static function getAverageByMovieId(int $movieId): float
    {
        $pdo = Database::getDatabaseConn();
        $table = static::$table;

        $sql = <<<SQL
            SELECT AVG(rating) as movie_rating
            FROM {$table}
            WHERE movie_id = :movie_id
        SQL;

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':movie_id', $movieId, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['movie_rating'] ? round((float) $result['movie_rating'], 1) : 0.0;
    }
}
