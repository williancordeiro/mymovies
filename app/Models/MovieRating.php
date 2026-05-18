<?php

namespace App\Models;

use Core\Database\ActiveRecord\Model;

class MovieRating extends Model
{
    protected static string $table = 'movie_ratings';

    protected static array $columns = [
        'user_id',
        'movie_id',
        'rating'
    ];

    protected ?int $id = null;
    protected int $user_id;
    protected int $movie_id;
    protected int $rating;
    protected ?string $created_at = null;
}
