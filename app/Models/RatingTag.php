<?php

namespace App\Models;

use Core\Database\ActiveRecord\Model;

/**
 * @property int    $id
 * @property string $name
 */
class RatingTag extends Model
{
    protected static string $table = 'rating_tags';

    protected static array $columns = [
        'name',
    ];

    protected ?int $id = null;
    protected string $name;
}