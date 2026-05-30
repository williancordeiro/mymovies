<?php

namespace App\Models;

use Core\Database\ActiveRecord\Model;

/**
 * @property int $id
 * @property string $title
 * @property string $overview
 * @property string $poster_path
 * @property string $release_date
 * @property float $vote_average
 * @property string $created_at
 */
class Movie extends Model
{
    protected static string $table = 'movies';

    protected static array $columns = [
        'id',
        'title',
        'overview',
        'poster_path',
        'release_date',
        'vote_average'
    ];

    protected ?int $id = null;
    protected string $title;
    protected ?string $overview = null;
    protected ?string $poster_path = null;
    protected ?string $release_date = null;
    protected ?float $vote_average = null;
    protected ?string $created_at = null;
}
