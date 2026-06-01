<?php

namespace Tests\Acceptance\Profile;

use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;
use Database\Populate\UsersPopulate;

class ProfileCest extends BaseAcceptanceCest
{
    private ?string $token = null;

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);
        UsersPopulate::populate();
        $this->token = $this->generateAuthToken($I);
    }

    public function testShouldUploadAvatarSuccessfully(AcceptanceTester $I): void
    {
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPost('/change/avatar', []);
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson(['error' => 'Arquivo inválido']);
    }

    public function testShouldViewUploadedAvatarPath(AcceptanceTester $I): void
    {
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGet('/profile');
        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.avatar_file');
    }

    public function testShouldRemoveAvatarSuccessfully(AcceptanceTester $I): void
    {
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendDelete('/change/avatar');
        $I->seeResponseCodeIs(200);
    }

    private function generateAuthToken(AcceptanceTester $I): string
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost('/auth/login', json_encode([
            'email' => 'example@email.com',
            'password' => 'password123'
        ]));
        $body = json_decode($I->grabResponse(), true);
        return $body['token'] ?? '';
    }
}
