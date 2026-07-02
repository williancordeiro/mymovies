<?php

namespace App\Models;

use Core\Database\ActiveRecord\Model;
use Core\Database\Database;

/**
 * @property int $id
 * @property string $tag
 */
class RatingTag extends Model
{
    protected static string $table = 'rating_tags';

    protected ?int $id = null;
    protected string $tag = null;
}
