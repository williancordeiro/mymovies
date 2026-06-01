<?php

namespace Tests\Integration\Access;

// Estendemos a sua classe base que configura o Guzzle e o Banco
class UserAccessTest extends BaseAccessTestCase
{
    public function test_authenticated_routes_should_require_authentication(): void
    {
        // 1. Testa a listagem do CRUD de usuários
        $responseList = $this->client->request('GET', '/list/users', [
            'http_errors' => false
        ]);
        // Se der 404 aqui, significa que a rota de listar não é /users. 
        // Se der 401, o middleware barrou com sucesso!
        $this->assertEquals(401, $responseList->getStatusCode());

        // 2. Testa a rota de deleção do CRUD de usuários (vimos que o método se chama delete no controller)
        $responseDelete = $this->client->request('DELETE', 'account/delete', [
            'json' => ['password' => '123456'],
            'http_errors' => false
        ]);
        $this->assertEquals(401, $responseDelete->getStatusCode());
    }
}
