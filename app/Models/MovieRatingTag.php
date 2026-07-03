<?php

namespace App\Models;

use Core\Database\ActiveRecord\Model;

/**
 * @property int $id
 * @property int $movie_rating_id
 * @property int $rating_tag_id
 */
class MovieRatingTag extends Model
{
    protected static string $table = 'movie_rating_tags';

    protected static array $columns = [
        'movie_rating_id',
        'rating_tag_id',
    ];

    protected ?int $id = null;
    protected int $movie_rating_id;
    protected int $rating_tag_id;
}