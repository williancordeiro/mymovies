<?php

namespace App\Models;

use Core\Database\ActiveRecord\Model;
use Core\Database\ActiveRecord\BelongsTo;
use Lib\Validations;

/**
 * @property int $id
 * @property int $movie_rating_id
 * @property int $rating_tag_id
 * @property string $created_at
 * @property string $updated_at
 */
class MovieRatingTag extends Model
{
    protected static string $table = 'movies_rating_tags';
    protected static array $columns = [
        'movie_rating_id',
        'rating_tag_id',
        'created_at',
        'updated_at'
    ];

    public function validates(): void
    {
        Validations::notEmpty('movie_rating_id', $this, 'A avaliação é obrigatória!');
        Validations::notEmpty('rating_tag_id', $this, 'A tag é obrigatória!');
    }

    public function movieRating(): BelongsTo
    {
        return $this->belongsTo(MovieRating::class, 'movie_rating_id');
    }

    public function ratingTag(): BelongsTo
    {
        return $this->belongsTo(RatingTag::class, 'rating_tag_id');
    }
}
