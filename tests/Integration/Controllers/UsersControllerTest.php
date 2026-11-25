<?php

namespace Tests\Integration\Controllers;

use App\Models\Movie;
use App\Models\MovieRating;
use App\Models\User;
use Core\Database\Database;
use Core\Router\Router;
use Lib\Authentication\Auth;
use Database\Populate\UsersPopulate;

class UsersControllerTest extends ControllerTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        UsersPopulate::populate();
    }

    public function test_should_login_successfully(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/auth/login';

        $response = $this->post(
            action: 'login',
            controllerName: 'App\Controllers\UsersController',
            params: [
                'email' => 'example@email.com',
                'password' => 'password123'
            ]
        );

        $data = json_decode($response, true);
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('user', $data);
        $this->assertEquals('example@email.com', $data['user']['email']);
    }

    public function test_should_not_login_with_wrong_password(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/auth/login';

        $response = $this->post(
            action: 'login',
            controllerName: 'App\Controllers\UsersController',
            params: [
                'email' => 'example@email.com',
                'password' => 'wrongpassword'
            ]
        );

        $data = json_decode($response, true);

        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Credenciais inválidas!', $data['message']);
    }

    public function test_should_not_login_with_wrong_email(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/auth/login';

        $response = $this->post(
            action: 'login',
            controllerName: 'App\Controllers\UsersController',
            params: [
                'email' => 'wrong@email.com',
                'password' => 'password123'
            ]
        );

        $data = json_decode($response, true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Credenciais inválidas!', $data['message']);
    }

    public function test_should_change_password_and_preserve_other_user_data(): void
    {
        $user = User::findBy(['email' => 'example@email.com']);
        $otherUser = User::findBy(['email' => 'admin@email.com']);
        $this->assertNotNull($user);
        $this->assertNotNull($otherUser);

        $originalPasswordHash = $user->encrypted_password;
        $originalUserData = [$user->username, $user->email, $user->handle, $user->role];
        $otherUserPasswordHash = $otherUser->encrypted_password;
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . Auth::generateToken($user);
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['REQUEST_URI'] = '/change/password';

        $response = $this->put('changePassword', 'App\\Controllers\\UsersController', [
            'current_password' => 'password123',
            'new_password' => '0',
        ]);

        $updatedUser = User::findBy(['email' => 'example@email.com']);
        $unchangedOtherUser = User::findBy(['email' => 'admin@email.com']);

        $this->assertSame(['message' => 'Sua senha foi atualizada!'], json_decode($response, true));
        $this->assertNotNull($updatedUser);
        $this->assertNotNull($unchangedOtherUser);
        $this->assertNotSame($originalPasswordHash, $updatedUser->encrypted_password);
        $this->assertTrue($updatedUser->authenticate('0'));
        $this->assertFalse($updatedUser->authenticate('password123'));
        $this->assertSame($originalUserData, [
            $updatedUser->username,
            $updatedUser->email,
            $updatedUser->handle,
            $updatedUser->role,
        ]);
        $this->assertSame($otherUserPasswordHash, $unchangedOtherUser->encrypted_password);
    }

    public function test_should_not_change_password_when_current_password_is_incorrect(): void
    {
        $user = User::findBy(['email' => 'example@email.com']);
        $this->assertNotNull($user);
        $originalPasswordHash = $user->encrypted_password;
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . Auth::generateToken($user);
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['REQUEST_URI'] = '/change/password';

        $response = $this->put('changePassword', 'App\\Controllers\\UsersController', [
            'current_password' => 'incorrect-password',
            'new_password' => 'new-password',
        ]);

        $unchangedUser = User::findBy(['email' => 'example@email.com']);

        $this->assertSame(['error' => 'A senha está incorreta!'], json_decode($response, true));
        $this->assertNotNull($unchangedUser);
        $this->assertSame($originalPasswordHash, $unchangedUser->encrypted_password);
        $this->assertTrue($unchangedUser->authenticate('password123'));
        $this->assertFalse($unchangedUser->authenticate('new-password'));
    }

    /**
     * @dataProvider invalidPasswordChangePayloads
     * @param array<string, mixed> $payload
     */
    public function test_should_reject_invalid_password_change_payloads(array $payload): void
    {
        $user = User::findBy(['email' => 'example@email.com']);
        $this->assertNotNull($user);
        $originalPasswordHash = $user->encrypted_password;
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . Auth::generateToken($user);
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['REQUEST_URI'] = '/change/password';

        $response = $this->put('changePassword', 'App\\Controllers\\UsersController', $payload);

        $unchangedUser = User::findBy(['email' => 'example@email.com']);

        $this->assertSame(
            ['error' => 'A senha atual e a nova senha são obrigatórias'],
            json_decode($response, true)
        );
        $this->assertNotNull($unchangedUser);
        $this->assertSame($originalPasswordHash, $unchangedUser->encrypted_password);
    }

    /** @return array<string, array{0: array<string, mixed>}> */
    public static function invalidPasswordChangePayloads(): array
    {
        return [
            'missing passwords' => [[]],
            'missing current password' => [['new_password' => 'new-password']],
            'missing new password' => [['current_password' => 'password123']],
            'empty current password' => [['current_password' => '', 'new_password' => 'new-password']],
            'empty new password' => [['current_password' => 'password123', 'new_password' => '']],
            'non-string current password' => [['current_password' => 123, 'new_password' => 'new-password']],
            'non-string new password' => [['current_password' => 'password123', 'new_password' => ['new-password']]],
        ];
    }

    public function test_should_require_authenticated_user_to_change_password(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['REQUEST_URI'] = '/change/password';

        $response = $this->put('changePassword', 'App\\Controllers\\UsersController', [
            'current_password' => 'password123',
            'new_password' => 'new-password',
        ]);

        $this->assertSame(['error' => 'Usuário não encontrado'], json_decode($response, true));
    }

    public function test_should_register_password_change_as_an_authenticated_put_route(): void
    {
        $passwordChangeRoute = null;
        $router = Router::getInstance();

        for ($index = 0; $index < $router->getRouteSize(); $index++) {
            $route = $router->getRoute($index);
            if ($route->getUri() === '/change/password') {
                $passwordChangeRoute = $route;
                break;
            }
        }

        $this->assertNotNull($passwordChangeRoute);
        $this->assertSame('PUT', $passwordChangeRoute->getMethod());
        $this->assertSame('App\\Controllers\\UsersController', $passwordChangeRoute->getControllerName());
        $this->assertSame('changePassword', $passwordChangeRoute->getActionName());

        $reflection = new \ReflectionObject($passwordChangeRoute);
        $middlewares = $reflection->getProperty('middlewares')->getValue($passwordChangeRoute);
        $this->assertContainsOnlyInstancesOf(\App\Middleware\Authenticate::class, $middlewares);
    }

    public function test_should_list_ratings_by_last_update_and_move_rerated_movie_to_top(): void
    {
        $user = User::findBy(['email' => 'example@email.com']);
        $this->assertNotNull($user);

        foreach ([1001, 1002, 1003, 1004] as $movieId) {
            Movie::saveFromTmdb([
                'id' => $movieId,
                'title' => "Movie {$movieId}",
            ]);
        }

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/movies/rate';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . Auth::generateToken($user);
        foreach ([1001, 1002, 1003, 1004] as $movieId) {
            $this->post('rate', 'App\\Controllers\\MoviesController', ['movie_id' => $movieId, 'rating' => 4]);
        }

        $pdo = Database::getDatabaseConn();
        $setUpdatedAt = $pdo->prepare(
            'UPDATE movies_rating SET created_at = :updated_at, updated_at = :updated_at
             WHERE user_id = :user_id AND movie_id = :movie_id'
        );
        foreach ([
            [1001, '2000-01-01 00:00:01'],
            [1002, '2000-01-01 00:00:02'],
            [1003, '2000-01-01 00:00:03'],
            [1004, '2000-01-01 00:00:03'],
        ] as [$movieId, $updatedAt]) {
            $setUpdatedAt->execute([
                ':user_id' => $user->id,
                ':movie_id' => $movieId,
                ':updated_at' => $updatedAt,
            ]);
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/users/' . $user->handle . '/ratings';
        $response = $this->get('ratings', 'App\\Controllers\\UsersController', ['handle' => $user->handle]);
        $ratings = json_decode($response, true)['ratings'];

        $this->assertSame([1004, 1003, 1002], array_column(array_slice($ratings, 0, 3), 'movie_id'));
        $this->assertSame([['movie_id' => 1004, 'rating' => 4]], array_slice($ratings, 0, 1));

        $this->post('rate', 'App\\Controllers\\MoviesController', ['movie_id' => 1001, 'rating' => 5]);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/users/' . $user->handle . '/ratings';
        $response = $this->get('ratings', 'App\\Controllers\\UsersController', ['handle' => $user->handle]);
        $ratings = json_decode($response, true)['ratings'];

        $this->assertSame(1001, $ratings[0]['movie_id']);
        $this->assertSame(5, $ratings[0]['rating']);
        $this->assertSame(1, (int) $pdo->query(
            "SELECT COUNT(*) FROM movies_rating WHERE user_id = {$user->id} AND movie_id = 1001"
        )->fetchColumn());
        $this->assertNotSame('2000-01-01 00:00:01', MovieRating::findBy([
            'user_id' => $user->id,
            'movie_id' => 1001,
        ])->updated_at);
    }

    public function test_should_return_not_found_for_ratings_of_unknown_handle(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/users/unknown/ratings';

        $response = $this->get('ratings', 'App\\Controllers\\UsersController', ['handle' => 'unknown']);

        $this->assertSame(['error' => 'Usuário não encontrado'], json_decode($response, true));
    }

    public function test_should_require_authentication_to_rate_a_movie(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/movies/rate';
        unset($_SERVER['HTTP_AUTHORIZATION']);

        $response = $this->post('rate', 'App\\Controllers\\MoviesController', ['movie_id' => 1001, 'rating' => 4]);

        $this->assertSame(['error' => 'Não autorizado'], json_decode($response, true));
    }
}
