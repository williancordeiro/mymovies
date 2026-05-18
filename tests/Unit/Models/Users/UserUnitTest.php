<?php

namespace Tests\Unit\Models\Users;

use PHPUnit\Framework\TestCase;
use App\Models\User;

class UserUnitTest extends TestCase
{
    public function test_should_initialize_user_with_correct_data_in_memory(): void
    {
        $userData = [
            'username' => 'tiago_dev',
            'email' => 'tiago@email.com',
            'role' => 'Default',
            'handle' => 'tiagodev99'
        ];

        $user = new User($userData);

        $this->assertEquals('tiago_dev', $user->username);
        $this->assertEquals('tiago@email.com', $user->email);
        $this->assertEquals('Default', $user->role);
        $this->assertEquals('tiagodev99', $user->handle);
    }

    public function test_should_allow_modifying_attributes_before_saving(): void
    {
        $user = new User(['username' => 'nome_antigo']);

        $user->username = 'nome_novo';

        $this->assertEquals('nome_novo', $user->username);
    }

    public function test_should_start_with_empty_errors_array(): void
    {
        $user = new User();

        $this->assertIsArray($user->errors());
        $this->assertEmpty($user->errors());
    }
}
