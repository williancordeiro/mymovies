<?php

namespace Tests\Integration\Access;

class AdminAccessTest extends BaseAccessTestCase
{
    public function test_should_access_login_route_without_token(): void
    {
        $response = $this->client->post('/auth/login', [
            'json' => ['email' => 'example@email.com', 'password' => 'password123']
        ]);
        $this->assertEquals(200, $response->getStatusCode());
    }

    // 2.1 - Rota autenticada, acessada somente por usuários autenticados
    public function test_should_not_access_admin_route_without_token(): void
    {
        $response = $this->client->get('/admin');
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_should_not_access_admin_route_with_normal_user_token(): void
    {
        $loginResponse = $this->client->post('/auth/login', [
            'json' => ['email' => 'example@email.com', 'password' => 'password123']
        ]);
        $token = json_decode((string) $loginResponse->getBody())->token;

        $response = $this->client->get('/admin', [
            'headers' => ['Authorization' => 'Bearer ' . $token]
        ]);
        $this->assertEquals(403, $response->getStatusCode());
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

    // 2.3 - Rota pública que não deve permitir usuários autenticados
    public function test_login_route_should_be_accessible_without_token(): void
    {
        $response = $this->client->post('/auth/login', [
            'json' => ['email' => 'example@email.com', 'password' => 'password123']
        ]);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
