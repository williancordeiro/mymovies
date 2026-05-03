<?php

namespace Tests\Acceptance\auth;

use App\Models\User;
use Database\Populate\UsersPopulate;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class AuthCest extends BaseAcceptanceCest
{
    public function _before(AcceptanceTester $page): void
    {
        parent::_before($page);
        UsersPopulate::populate();
    }

    public function testShouldNotAccessAdminWithoutToken(AcceptanceTester $page): void
    {
        $page->sendGet('/admin');
        $page->seeResponseCodeIs(401);
    }

    public function testShouldNotLoginWithWrongCredentials(AcceptanceTester $page): void
    {
        $page->haveHttpHeader('Content-Type', 'application/json');
        $page->sendPost('/auth/login', json_encode([
            'email' => 'email@errado.com',
            'password' => 'senha_errada'
        ]));
        $page->seeResponseCodeIs(401);
        $page->seeResponseContainsJson(['error' => 'E-mail ou senha inválidos']);
    }

    public function testShouldLoginSuccessfully(AcceptanceTester $page): void
    {
        $page->haveHttpHeader('Content-Type', 'application/json');
        $page->sendPost('/auth/login', json_encode([
            'email' => 'admin@email.com',
            'password' => 'adminpass'
        ]));
        $page->seeResponseCodeIs(200);
        $page->seeResponseJsonMatchesJsonPath('$.token');
    }

    public function testShouldLogoutSuccessfully(AcceptanceTester $page): void
    {
        $page->haveHttpHeader('Content-Type', 'application/json');
        $page->sendPost('/auth/login', json_encode([
        'email' => 'admin@email.com',
        'password' => 'adminpass'
        ]));
        $token = $page->grabDataFromResponseByJsonPath('$.token')[0];

        $page->haveHttpHeader('Content-Type', 'application/json');
        $page->haveHttpHeader('Authorization', 'Bearer ' . $token);
        $page->sendPost('/auth/logout', '{}');
        $page->seeResponseCodeIs(200);
        $page->seeResponseContainsJson(['message' => 'Logout realizado com sucesso']);
    }
}
