<?php

namespace App\Models;

use Lib\Validations;
use Core\Database\ActiveRecord\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $description
 * @property int $release_year
 * @property string $poster_url
 * @property int $rating
 * @property string $status
 * @property string $created_at
 * @property string $updated_at
 */
class CustomMovie extends Model
{
    protected static string $table = 'custom_movies';
    protected static array $columns = [
        'user_id',
        'title',
        'description',
        'release_year',
        'poster_url',
        'rating',
        'status',
        'created_at',
        'updated_at'
    ];

    public function validates(): void
    {
        Validations::notEmpty('user_id', $this, 'O ID do usuário é obrigatório!');
        Validations::notEmpty('title', $this, 'O título é obrigatório!');

        if ($this->description !== null && $this->description !== '') {
            if (strlen($this->description) < 10) {
                $this->addError('description', 'A descrição deve ter pelo menos 10 caracteres.');
            }
        }

        if ($this->rating !== null && $this->rating !== '') {
            if (!is_numeric($this->rating) || (int)$this->rating < 1 || (int)$this->rating > 5) {
                $this->addError('rating', 'A nota deve ser um número entre 1 e 5.');
            }
        }

        // Year must be a valid number and not more than 10 years in the future
        if ($this->release_year !== null && $this->release_year !== '') {
            $currentYear = (int)date('Y');
            if (!is_numeric($this->release_year) || (int)$this->release_year < 1888 || (int)$this->release_year > $currentYear + 10) {
                $this->addError('release_year', "Informe um ano de lançamento válido (entre 1888 e " . ($currentYear + 10) . ").");
            }
        }

        // Status is required and must be one of the allowed values
        if ($this->status === null || $this->status === '' ) {
            $this->addError('status', 'O status é obrigatório.');
        } else {
            $validStatuses = ['Vou assistir', 'Assistido', 'Não terminei'];
            if (!in_array($this->status, $validStatuses)) {
                $this->addError('status', 'Status inválido. Escolha entre: Vou assistir, Assistido ou Não terminei.');
            }
        }
    }
}
