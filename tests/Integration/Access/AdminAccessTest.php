<?php

namespace Tests\Integration\Access;

class AdminAccessTest extends BaseAccessTestCase
{
    public function test_should_access_login_route_without_token(): void
    {
        $response = $this->client->post('/auth/login', [
            'json' => ['email' => 'example@email.com', 'password' => 'password123'],
            'http_errors' => false
        ]);

        $this->assertTrue(in_array($response->getStatusCode(), [200, 401]));
    }

    public function test_should_not_access_admin_route_without_token(): void
    {
        $response = $this->client->get('/admin');
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_should_not_access_admin_route_with_normal_user_token(): void
    {
        $user = \App\Models\User::findByEmail('example@email.com');
        $token = $user ? \Lib\Authentication\Auth::generateToken($user) : 'token_reserva';

        $response = $this->client->get('/admin', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'http_errors' => false
        ]);


        $this->assertTrue(in_array($response->getStatusCode(), [401, 403]));
    }

    public function test_should_access_admin_route_with_admin_token(): void
    {
        $loginResponse = $this->client->post('/auth/login', [
            'json' => ['email' => 'admin@email.com', 'password' => 'adminpass']
        ]);
        $token = json_decode((string) $loginResponse->getBody())->token;

        $response = $this->client->get('/admin', [
            'headers' => ['Authorization' => 'Bearer ' . $token]
        ]);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_login_route_should_be_accessible_without_token(): void
    {
        $response = $this->client->post('/auth/login', [
            'json' => ['email' => 'example@email.com', 'password' => 'password123'],
            'http_errors' => false
        ]);


        $this->assertTrue(in_array($response->getStatusCode(), [200, 401]));
    }
}
