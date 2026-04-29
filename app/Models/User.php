<?php

namespace App\Models;

use Lib\Validations;
use Core\Database\ActiveRecord\Model;

/**
 * @property int $id
 * @property string $username
 * @property string $email
 * @property string $encrypted_password
 * @property string $avatar_file
 * @property string $admin
 * @property string $created_at
 * @property string $updated_at
 */
class User extends Model {
    protected static string $table = 'users';
    protected static array $columns = ['username', 'email', 'encrypted_password', 'avatar_file', 'admin', 'created_at', 'updated_at'];

    protected ?string $password = null;
    protected ?string $password_confirmation = null;

    public function validates(): void {
        Validations::notEmpty('username', $this);
        Validations::notEmpty('email', $this);

        Validations::uniqueness('email', $this);

        if ($this->newRecord()) {
            Validations::passwordConfirmation($this);
        }
    }

    public function authenticate(string $password): bool {
        if ($this->encrypted_password == null) {
            return false;
        }

        return password_verify($password, $this->encrypted_password);
    }

    public static function findByEmail(string $email): User | null {
        return User::findBy(['email' => $email]);
    }

    public function __set(string $property, mixed $value): void {
        parent::__set($property, $value);

        if (
            $property === 'password' &&
            $this->newRecord() &&
            $value !== null && $value !== ''
        ) {
            $this->encrypted_password = password_hash($value, PASSWORD_DEFAULT);
        }
    }
}
