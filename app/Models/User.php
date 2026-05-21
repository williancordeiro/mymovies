<?php

namespace App\Models;

use Lib\Validations;
use Core\Database\ActiveRecord\Model;

/**
 * @property int $id
 * @property string $username
 * @property string $handle
 * @property string $email
 * @property string $encrypted_password
 * @property string $avatar_file
 * @property string $role
 * @property string $created_at
 * @property string $updated_at
 */
class User extends Model
{
    protected static string $table = 'users';
    protected static array $columns = [
        'username',
        'handle',
        'email',
        'encrypted_password',
        'avatar_file',
        'role',
        'created_at',
        'updated_at'
        ];

    protected ?string $password = null;
    protected ?string $password_confirmation = null;

    public function validates(): void
    {
        Validations::notEmpty('username', $this, 'O nome de usuário é obrigatório!');
        Validations::notEmpty('email', $this, 'O e-mail é obrigatório!');
        Validations::notEmpty('handle', $this, 'O indentificador é obrigatório!');

        if ($this->newRecord()) {
            Validations::notEmpty('password', $this, 'A senha é obrigatória!');
        }


        Validations::uniqueness('email', $this, 'Esse email já esta em uso!');
        Validations::uniqueness('handle', $this, 'Esse indentificador já esta em uso!');
    }

    public function authenticate(string $password): bool
    {
        if ($this->encrypted_password == null) {
            return false;
        }

        return password_verify($password, $this->encrypted_password);
    }

    public static function findByEmail(string $email): User | null
    {
        return User::findBy(['email' => $email]);
    }

    public static function findByUsername(string $username): User | null
    {
        return User::findBy(['username' => $username]);
    }

    public static function findByHandle(string $handle): User | null
    {
        return User::findBy(['handle' => $handle]);
    }

    public function __set(string $property, mixed $value): void
    {
        parent::__set($property, $value);

        if (
            $property === 'password' &&
            $this->newRecord() &&
            $value !== null && $value !== ''
        ) {
            $this->encrypted_password = password_hash($value, PASSWORD_DEFAULT);
        }
    }

    public function avatarPath(): string
    {
        if (!$this->avatar_file || $this->avatar_file === 'avatar.png') {
            return "/assets/images/defaults/avatar.png";
        }

        return "/assets/uploads/" . $this->avatar_file;
    }

    public function jsonSerialize(): mixed
    {
        $data = parent::jsonSerialize();
        unset($data['encrypted_password']);
        return $data;
    }
}
