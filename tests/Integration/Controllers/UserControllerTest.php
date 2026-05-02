<?php

namespace Tests\Integration\Controllers;

use App\Models\User;
use Database\Populate\UsersPopulate;

class UserControllerTest extends ControllerTestCase
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
            controllerName: 'App\Controllers\UserController',
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
            controllerName: 'App\Controllers\UserController',
            params: [
                'email' => 'example@email.com',
                'password' => 'wrongpassword'
            ]
        );

        $data = json_decode($response, true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('E-mail ou senha inválidos', $data['error']);
    }

    public function test_should_not_login_with_wrong_email(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/auth/login';

        $response = $this->post(
            action: 'login',
            controllerName: 'App\Controllers\UserController',
            params: [
                'email' => 'wrong@email.com',
                'password' => 'password123'
            ]
        );

        $data = json_decode($response, true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('E-mail ou senha inválidos', $data['error']);
    }
}
