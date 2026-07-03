<?php

namespace App\Models;

use Core\Database\ActiveRecord\Model;
use Lib\Validations;
use Core\Database\ActiveRecord\HasMany;
use Core\Database\ActiveRecord\BelongsTo;
use Core\Database\ActiveRecord\BelongsToMany;
use Core\Database\Database;
use PDO;

/**
 * @property int $id
 * @property string $description
 * @property string $created_at
 */
class Tag extends Model
{
    protected static string $table = 'rating_tags';

    protected static array $columns = [
        'description',
        'created_at'
    ];

    protected ?int $id = null;
    protected string $description;
    protected ?string $created_at = null;

    public function validates(): void
    {
        if (empty($this->description)) {
            $this->addError('description', 'A descrição não pode estar vazia!');
        }
    }

    public function movieRatings(): BelongsToMany
    {
        return $this->BelongsToMany(MovieRating::class, 'movies_rating_tags', 'rating_tag_id', 'movie_rating_id');
    }
}