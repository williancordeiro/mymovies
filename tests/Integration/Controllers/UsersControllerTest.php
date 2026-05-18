<?php

namespace Tests\Integration\Controllers;

use App\Models\User;
use Database\Populate\UsersPopulate;

class UsersControllerTest extends ControllerTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        UsersPopulate::populate();
    }

    public function test_should_login_successfully(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/auth/login';

        $response = $this->post(
            action: 'login',
            controllerName: 'App\Controllers\UsersController',
            params: [
                'email' => 'example@email.com',
                'password' => 'password123'
            ]
        );

        $data = json_decode($response, true);
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('user', $data);
        $this->assertEquals('example@email.com', $data['user']['email']);
    }

    public function test_should_not_login_with_wrong_password(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/auth/login';

        $response = $this->post(
            action: 'login',
            controllerName: 'App\Controllers\UsersController',
            params: [
                'email' => 'example@email.com',
                'password' => 'wrongpassword'
            ]
        );

        $data = json_decode($response, true);

        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Credenciais inválidas!', $data['message']);
    }

    public function test_should_not_login_with_wrong_email(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/auth/login';

        $response = $this->post(
            action: 'login',
            controllerName: 'App\Controllers\UsersController',
            params: [
                'email' => 'wrong@email.com',
                'password' => 'password123'
            ]
        );

        $data = json_decode($response, true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Credenciais inválidas!', $data['message']);
    }
}
