<?php

namespace App\Models;

use Core\Database\ActiveRecord\Model;
use Core\Database\ActiveRecord\BelongsTo;
use Core\Database\ActiveRecord\HasMany;
use Lib\Validations;
use Core\Database\Database;
use PDO;
use Exception;

/**
 * @property int $id
 * @property int $movie_rating_id
 * @property int $tag_id
 * @property string $created_at
 * @property string $updated_at
 */

class MoviesRatingTags extends Model
{
    protected static string $table = 'movies_rating_tags';

    protected static array $columns = [
        'movie_rating_id',
        'rating_tag_id',
        'created_at',
        'updated_at'
    ];

    protected ?int $id = null;
    protected int $movie_rating_id;
    protected int $rating_tag_id;
    protected ?string $created_at = null;
    protected ?string $updated_at = null;

    public function validates(): void
    {
        if (empty($this->movie_rating_id)) {
            $this->addError('movie_rating_id', 'O ID da avaliação do filme não pode estar vazio!');
        }

        if (empty($this->rating_tag_id)) {
            $this->addError('rating_tag_id', 'O ID da tag não pode estar vazio!');
        }

        if (!empty($this->movie_rating_id) && !empty($this->rating_tag_id)) {
            Validations::uniqueness(['movie_rating_id', 'rating_tag_id'], $this, 'Essa tag já foi adicionada a essa avaliação de filme!');
        }
    }

    public function movieRating(): BelongsTo
    {
        return $this->belongsTo(MovieRating::class, 'movie_rating_id');
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class, 'rating_tag_id');
    }

    public static function syncForRating(int $movieRatingId, array $tagIds): void
    {
        $pdo = Database::getDatabaseConn();
        $table = static::$table;

        $sql = <<<SQL
            DELETE FROM {$table}
            WHERE movie_rating_id = :movie_rating_id
        SQL;

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':movie_rating_id' => $movieRatingId]);

        foreach ($tagIds as $tagId) {
            $instance = new self();
            $instance->movie_rating_id = $movieRatingId;
            $instance->rating_tag_id = (int) $tagId;

            if (!$instance->save()) {
                throw new Exception('Erro ao salvar a tag para a avaliação do filme: ' . implode(', ', $instance->getErrors()));
            }
        }
    }
}