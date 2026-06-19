<?php

namespace App\Models;

use Core\Database\ActiveRecord\Model;
use Core\Database\ActiveRecord\BelongsTo;
use App\Services\ProfileImages;
use Lib\Validations;

/**
 * @property int $id
 * @property int $user_id
 * @property string $image_file
 * @property string $created_at
 */
class UserImage extends Model
{
    protected static string $table = 'user_images';
    protected static array $columns = [
        'user_id',
        'image_file',
        'created_at',
    ];

    public function validates(): void
    {
        Validations::notEmpty('image_file', $this, 'O arquivo é obrigatório!');
        Validations::notEmpty('user_id', $this, 'O usuário é obrigatório!');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function path(): string
    {
        return (new ProfileImages($this, [], 'image_file'))->path();
    }
}