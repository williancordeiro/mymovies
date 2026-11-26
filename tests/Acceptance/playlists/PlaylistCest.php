<?php

namespace Tests\Acceptance\playlists;

use App\Models\Movie;
use Database\Populate\UsersPopulate;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class PlaylistCest extends BaseAcceptanceCest
{
    private string $token;
    private string $otherToken;

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);
        UsersPopulate::populate();
        Movie::saveFromTmdb(['id' => 101, 'title' => 'Um', 'poster_path' => '/1.jpg', 'release_date' => '2020-01-01']);
        Movie::saveFromTmdb(['id' => 102, 'title' => 'Dois', 'poster_path' => '/2.jpg', 'release_date' => '2020-01-02']);
        $this->token = $this->login($I, 'example@email.com', 'password123');
        $this->otherToken = $this->login($I, 'admin@email.com', 'adminpass');
    }

    public function crudPaginationPrivacyAndInvalidBodies(AcceptanceTester $I): void
    {
        $this->auth($I, $this->token);
        $I->sendPost('/playlists', json_encode(['title' => 'Assistir', 'movie_ids' => [101, 102]]));
        $I->seeResponseCodeIs(201);
        $id = json_decode($I->grabResponse(), true)['data']['id'];
        $I->sendPut('/playlists/' . $id, json_encode(['title' => 'Assistir depois']));
        $I->seeResponseCodeIs(200);
        $I->sendGet('/playlists/' . $id . '?per_page=1&page=2');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['total' => 2, 'page' => 2, 'per_page' => 1, 'movies' => [['movie_id' => 102]]]);
        $I->sendGet('/playlists/' . $id . '?per_page=1&page=3');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['total' => 2, 'movies' => []]);
        $this->auth($I, $this->otherToken);
        $I->sendGet('/playlists/' . $id);
        $I->seeResponseCodeIs(404);
        $I->sendPut('/playlists/' . $id, json_encode(['title' => 'intrusão']));
        $I->seeResponseCodeIs(404);
        $I->sendDelete('/playlists/' . $id);
        $I->seeResponseCodeIs(404);
        $I->sendGet('/playlists');
        $I->seeResponseContainsJson(['total' => 0, 'playlists' => [], 'pages' => 0]);
        $this->auth($I, $this->token);
        $I->sendPut('/playlists/' . $id, json_encode(['title' => 'Vazia', 'movie_ids' => []]));
        $I->seeResponseCodeIs(200);
        $I->sendGet('/playlists/' . $id);
        $I->seeResponseContainsJson(['total' => 0, 'movies' => [], 'pages' => 0]);
        $I->sendDelete('/playlists/' . $id);
        $I->seeResponseCodeIs(200);
        $I->sendGet('/playlists/' . $id);
        $I->seeResponseCodeIs(404);

        $I->sendPost('/playlists', '{invalid');
        $I->seeResponseCodeIs(400);
        $I->sendPost('/playlists', json_encode(['title' => 'x', 'movie_ids' => [101, '101']]));
        $I->seeResponseCodeIs(400);
        $I->sendGet('/playlists?page=0');
        $I->seeResponseCodeIs(400);
        $I->sendGet('/playlists?page=9223372036854775807&per_page=2');
        $I->seeResponseCodeIs(400);
        $I->sendPost('/playlists', json_encode(['title' => 'x', 'user_id' => 1]));
        $I->seeResponseCodeIs(400);
    }

    private function auth(AcceptanceTester $I, string $token): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $token);
    }

    private function login(AcceptanceTester $I, string $email, string $password): string
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost('/auth/login', json_encode(['email' => $email, 'password' => $password]));
        return json_decode($I->grabResponse(), true)['token'];
    }
}
