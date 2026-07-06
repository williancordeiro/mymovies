<?php

namespace Tests\Acceptance\movies;

use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;
use Database\Populate\UsersPopulate;

class MoviesSearchCest extends BaseAcceptanceCest
{
    private ?string $token = null;

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);
        UsersPopulate::populate();

        $loginData = $this->login($I, 'example@email.com', 'password123');
        $this->token = $loginData['token'] ?? '';
    }

    public function testShouldNotSearchWithQueryShorterThanThreeCharacters(AcceptanceTester $I): void
    {
        $I->sendGet('/movies/search?q=ba');
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson(['error' => 'A consulta deve ter pelo menos 1 caracteres']);
    }

    public function testShouldNotSearchWithoutQueryParam(AcceptanceTester $I): void
    {
        $I->sendGet('/movies/search');
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson(['error' => 'A consulta deve ter pelo menos 1 caracteres']);
    }

    public function testShouldNotSearchWithEmptyQueryParam(AcceptanceTester $I): void
    {
        $I->sendGet('/movies/search?q=');
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson(['error' => 'A consulta deve ter pelo menos 1 caracteres']);
    }

    // Depende de chamada real à API do TMDB, só passa em ambiente local com token válido configurado.
    // Não roda no pipeline de CI/CD pelo mesmo motivo dos testes comentados em MoviesRatingCest.
    public function testShouldSearchMoviesSuccessfullyAsAnonymousUser(AcceptanceTester $I): void
    {
        $I->sendGet('/movies/search?q=bat');
        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.results');
    }

    public function testShouldSearchMoviesSuccessfullyAsAuthenticatedUser(AcceptanceTester $I): void
    {
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token);
        $I->sendGet('/movies/search?q=bat');
        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.results');
        // Cada item de $.results deveria conter as chaves mymovies_rating_average e user_rating
    }

    public function testShouldLimitSearchResultsToTwentyMovies(AcceptanceTester $I): void
    {
        $I->sendGet('/movies/search?q=the');
        $I->seeResponseCodeIs(200);
        $response = json_decode($I->grabResponse(), true);
        $I->seeResponseJsonMatchesJsonPath('$.results[19]');
        $I->dontSeeResponseJsonMatchesJsonPath('$.results[20]');
        // Garante que o array tem itens, mas não passa de 20 (índice 20 não existe)
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
