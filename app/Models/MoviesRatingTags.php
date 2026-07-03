<?php

namespace App\Models;

use Core\Database\ActiveRecord\Model;
use Core\Database\ActiveRecord\BelongsTo;
use Core\Database\ActiveRecord\HasMany;
use Lib\Validations;
use Core\Database\Database;
use PDO;

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
        'tag_id',
        'created_at',
        'updated_at'
    ];

    protected ?int $id = null;
    protected int $movie_rating_id;
    protected int $tag_id;
    protected ?string $created_at = null;
    protected ?string $updated_at = null;

    public function validates(): void
    {
        if (empty($this->movie_rating_id)) {
            $this->addError('movie_rating_id', 'O ID da avaliação do filme não pode estar vazio!');
        }

        if (empty($this->tag_id)) {
            $this->addError('tag_id', 'O ID da tag não pode estar vazio!');
        }
    }

    public function movieRating(): BelongsTo
    {
        return $this->belongsTo(MovieRating::class, 'movie_rating_id');
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class, 'tag_id');
    }
}