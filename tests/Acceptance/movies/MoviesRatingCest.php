<?php

namespace Tests\Acceptance\movies;

use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;
use Database\Populate\UsersPopulate;
use App\Models\Movie;

class MoviesRatingCest extends BaseAcceptanceCest
{
    private ?string $token = null;
    private string $userHandle = '';

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);
        UsersPopulate::populate();

        Movie::saveFromTmdb([
            'id' => 1226863,
            'title' => 'Filme de Teste',
            'overview' => 'Um filme fictício',
            'poster_path' => '/teste.png',
            'release_date' => '2024-01-01',
            'vote_average' => 7.5
        ]);

        $loginData = $this->login($I, 'example@email.com', 'password123');
        $this->token = $loginData['token'] ?? '';
        $this->userHandle = $loginData['user']['handle'] ?? '';
    }

    public function testShouldRateMovieSuccessfully(AcceptanceTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPost('/movies/rate', json_encode([
            'movie_id' => 1226863,
            'rating' => 5
        ]));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['success' => true]);
        $I->seeResponseJsonMatchesJsonPath('$.data.average_rating');
    }

    public function testShouldNotRateWithIncompleteData(AcceptanceTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPost('/movies/rate', json_encode([
            'movie_id' => 1226863
            // sem o rating
        ]));
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson(['error' => 'Dados incompletos']);
    }

    public function testShouldNotRateWithoutAuthentication(AcceptanceTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost('/movies/rate', json_encode([
            'movie_id' => 1226863,
            'rating' => 5
        ]));
        $I->seeResponseCodeIs(401);
    }

    public function testShouldViewUserRatingsSuccessfully(AcceptanceTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPost('/movies/rate', json_encode(['movie_id' => 1226863, 'rating' => 4]));

        $I->sendGet('/users/' . $this->userHandle . '/ratings');
        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.ratings');
    }

    public function testShouldReturnEmptyRatingsForUserWithoutRatings(AcceptanceTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost('/auth/register', json_encode([
        'username' => 'NoRatingsUser',
        'email' => 'noratings_' . uniqid() . '@email.com',
        'password' => '123456'
        ]));
        $registerData = json_decode($I->grabResponse(), true);
        $handle = $registerData['user']['handle'];

        $I->sendGet('/users/' . $handle . '/ratings');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['ratings' => []]);
    }

    public function testShouldReturnNotFoundForNonExistentHandle(AcceptanceTester $I): void
    {
        $I->sendGet('/users/handle_inexistente_123/ratings');
        $I->seeResponseCodeIs(404);
        $I->seeResponseContainsJson(['error' => 'Usuário não encontrado']);
    }

    public function testShouldRemoveRatingSuccessfully(AcceptanceTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendPost('/movies/rate', json_encode(['movie_id' => 1226863, 'rating' => 4]));

        $I->sendDelete('/movies/rate/1226863'); // corrigido
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['success' => true]);
    }

    public function testShouldNotRemoveNonExistentRating(AcceptanceTester $I): void
    {
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendDelete('/movies/rate/');
        $I->seeResponseCodeIs(404);
        $I->seeResponseContainsJson(['error' => 'Avaliação não encontrada']);
    }

    public function testShouldNotRemoveRatingWithoutAuthentication(AcceptanceTester $I): void
    {
        $I->sendDelete('/movies/rate/1226863');
        $I->seeResponseCodeIs(401);
    }

    /**
    * @return array<string, mixed>
    */
    private function login(AcceptanceTester $I, string $email, string $password): array
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost('/auth/login', json_encode([
            'email' => $email,
            'password' => $password
        ]));
        return json_decode($I->grabResponse(), true);
    }
}
