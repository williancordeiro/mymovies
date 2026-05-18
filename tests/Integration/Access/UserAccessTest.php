<?php

namespace Tests\Integration\Access;

// Estendemos a sua classe base que configura o Guzzle e o Banco
class UserAccessTest extends BaseAccessTestCase
{
    public function test_authenticated_routes_should_require_authentication(): void
    {
        $responseList = $this->client->request('GET', '/users');
        $this->assertEquals(401, $responseList->getStatusCode());

        $responseUpdate = $this->client->request('PUT', '/profile/update', [
            'json' => ['username' => 'GhostUser']
        ]);
        $this->assertEquals(401, $responseUpdate->getStatusCode());

        $responseDelete = $this->client->request('DELETE', '/account/delete', [
            'json' => ['password' => '123456']
        ]);
        $this->assertEquals(401, $responseDelete->getStatusCode());
    }
}
