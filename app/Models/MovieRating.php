<?php

namespace App\Models;

use Core\Database\ActiveRecord\Model;
use Lib\Validations;
use Core\Database\ActiveRecord\HasMany;
use Core\Database\ActiveRecord\BelongsTo;
use Core\Database\Database;
use Core\Database\ActiveRecord\BelongsToMany;
use App\Models\RatingTag;
use PDO;

/**
 * @property int $id
 * @property int $user_id
 * @property int $movie_id
 * @property int $rating
 * @property string $created_at
 * @property string $updated_at
 */

class MovieRating extends Model
{
    protected static string $table = 'movies_rating';

    protected static array $columns = [
        'user_id',
        'movie_id',
        'rating',
        'created_at',
        'updated_at'
    ];

    protected ?int $id = null;
    protected int $user_id;
    protected int $movie_id;
    protected int $rating;
    protected ?string $created_at = null;
    protected ?string $updated_at = null;

    public function validates(): void
    {
        if ($this->rating < 1 || $this->rating > 5) {
            $this->addError('rating', 'A avaliação deve ser entre 1 e 5!');
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function getRatingForUserId(int $userId): MovieRating | null
    {
        return MovieRating::findBy(['user_id' => $userId]);
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
    
    public function tags(): BelongsToMany
    {
        return $this->BelongsToMany(
            RatingTag::class,
            'movie_rating_tags',
            'movie_rating_id',
            'rating_tag_id'
        );
}
}
