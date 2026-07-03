<?php

namespace App\Models;

use Core\Database\ActiveRecord\Model;
use Lib\Validations;

/**
 * @property int $id
 * @property string $tag
 * @property string $created_at
 * @property string $updated_at
 */
class RatingTag extends Model
{
    protected static string $table = 'rating_tags';
    protected static array $columns = [
        'tag',
        'created_at',
        'updated_at'
    ];

    public function validates(): void
    {
        Validations::notEmpty('tag', $this, 'A tag não pode ser vazia!');
    }
}
