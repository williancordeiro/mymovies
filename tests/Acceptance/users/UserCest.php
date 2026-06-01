<?php

namespace Tests\Acceptance\users;

use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;
use Database\Populate\UsersPopulate;

class UserCest extends BaseAcceptanceCest
{
    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);
        UsersPopulate::populate();
    }

    public function testShouldNotRegisterWithInvalidData(AcceptanceTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost('/auth/register', json_encode([
            'username' => '',
            'email' => 'invalido',
            'password' => '123456'
        ]));
        $I->seeResponseCodeIs(422);
        $I->seeResponseJsonMatchesJsonPath('$.errors');
    }

    public function testShouldRegisterSuccessfully(AcceptanceTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost('/auth/register', json_encode($this->fakeUser()));
        $I->seeResponseCodeIs(201);
        $I->seeResponseJsonMatchesJsonPath('$.token');
    }

    public function testShouldNotUpdateWithInvalidData(AcceptanceTester $I): void
    {
        $login = $this->login($I, 'example@email.com', 'password123');
        $token = $login['token'];
        $this->auth($I, $token);
        $I->sendPut('/profile/update', json_encode(['username' => '']));
        $I->seeResponseCodeIs(422);
    }

    public function testShouldUpdateSuccessfully(AcceptanceTester $I): void
    {
        $login = $this->login($I, 'example@email.com', 'password123');
        $token = $login['token'];
        $this->auth($I, $token);
        $I->sendPut('/profile/update', json_encode([
            'username' => 'UpdatedUser_' . uniqid()
        ]));
        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.token');
    }

    public function testShouldDeleteAccount(AcceptanceTester $I): void
    {
        $login = $this->login($I, 'example@email.com', 'password123');
        $token = $login['token'];
        $this->auth($I, $token);
        $I->sendDelete('/account/delete', ['password' => 'password123']);
        $I->seeResponseCodeIs(200);
    }

    public function testShouldListAllUsers(AcceptanceTester $I): void
    {
        $user = $this->fakeUser();

        $I->sendPost('/auth/register', $user);

        $login = $this->login($I, $user['email'], $user['password']);

        if (!isset($login['token'])) {
            throw new \Exception('Login falhou: ' . json_encode($login));
        }

        $token = $login['token'];

        $this->auth($I, $token);

        $I->sendGet('/list/users');

        $I->seeResponseCodeIs(200);
    }

    public function testShouldPaginateUsersIfAdmin(AcceptanceTester $I): void
    {
        $login = $this->login($I, 'admin@email.com', 'adminpass');
        $token = $login['token'];
        $this->auth($I, $token);
        $I->sendGet('/list/users?page=1');
        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.users');
        $I->seeResponseJsonMatchesJsonPath('$.page');
        $I->seeResponseJsonMatchesJsonPath('$.pages');
    }

    /**
     * @return array<string, mixed>
     */
    private function fakeUser(): array
    {
        return [
            'username' => 'NovoUser_' . uniqid(),
            'email' => 'novo_' . uniqid() . '@email.com',
            'password' => '123456'
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function login(AcceptanceTester $I, string $email, string $password): array
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->haveHttpHeader('Accept', 'application/json');
        $I->sendPost('/auth/login', json_encode([
        'email' => $email,
        'password' => $password
        ]));
        return json_decode($I->grabResponse(), true);
    }

    private function auth(AcceptanceTester $I, string $token): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
    }
}
