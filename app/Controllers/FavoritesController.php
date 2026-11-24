<?php

namespace App\Controllers;

use App\Models\Movie;
use App\Models\MovieFavorite;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\TheMovieDatabase;
use PDOException;

class FavoritesController extends Controller
{
    public function create(Request $request): void
    {
        $user = $this->currentUser();
        if (!$user) {
            $this->json(['error' => 'Não autorizado'], 401);
            return;
        }

        $data = $this->requestData($request);
        $movieId = $this->positiveMovieId($data['movie_id'] ?? null);
        if ($movieId === null) {
            $this->json(['error' => 'movie_id deve ser um inteiro positivo'], 400);
            return;
        }

        try {
            if (!Movie::findById($movieId) && !$this->ensureLocalMovie($movieId)) {
                return;
            }

            if (MovieFavorite::findBy(['user_id' => $user->id, 'movie_id' => $movieId])) {
                $this->favoriteResponse($movieId, 200);
                return;
            }

            $favorite = new MovieFavorite(['user_id' => $user->id, 'movie_id' => $movieId]);
            $favorite->save();
            $this->favoriteResponse($movieId, 201);
        } catch (PDOException $exception) {
            if ($this->isDuplicateFavorite($exception)) {
                $this->favoriteResponse($movieId, 200);
                return;
            }

            $this->json(['error' => 'Não foi possível salvar o favorito'], 500);
        } catch (\Throwable $exception) {
            $this->json(['error' => 'Não foi possível salvar o favorito'], 500);
        }
    }

    public function delete(Request $request): void
    {
        $user = $this->currentUser();
        if (!$user) {
            $this->json(['error' => 'Não autorizado'], 401);
            return;
        }

        $movieId = $this->positiveMovieId($request->getParam('movie_id'));
        if ($movieId === null) {
            $this->json(['error' => 'movie_id deve ser um inteiro positivo'], 400);
            return;
        }

        try {
            $favorite = MovieFavorite::findBy(['user_id' => $user->id, 'movie_id' => $movieId]);
            if ($favorite) {
                $favorite->destroy();
            }
            $this->json(['success' => true, 'data' => [
                'movie_id' => $movieId,
                'user_favorited' => false,
            ]]);
        } catch (\Throwable $exception) {
            $this->json(['error' => 'Não foi possível remover o favorito'], 500);
        }
    }

    public function index(Request $request): void
    {
        $user = $this->currentUser();
        if (!$user) {
            $this->json(['error' => 'Não autorizado'], 401);
            return;
        }

        $page = $this->positiveInteger($request->getParam('page', 1));
        $perPage = $this->positiveInteger($request->getParam('per_page', 20));
        if ($page === null || $perPage === null) {
            $this->json(['error' => 'page e per_page devem ser inteiros positivos'], 400);
            return;
        }
        $perPage = min($perPage, 100);
        if ($page > intdiv(PHP_INT_MAX, $perPage) + 1) {
            $this->json(['error' => 'page e per_page devem ser inteiros positivos'], 400);
            return;
        }

        try {
            $total = MovieFavorite::countForUser($user->id);
            $offset = ($page - 1) * $perPage;
            $rows = MovieFavorite::listForUser($user->id, $perPage, $offset);
            $favorites = array_map(static fn (array $movie): array => [
                'movie_id' => (int) $movie['movie_id'],
                'title' => $movie['title'],
                'poster_path' => $movie['poster_path'],
                'release_date' => $movie['release_date'],
                'user_favorited' => true,
            ], $rows);

            $this->json([
                'favorites' => $favorites,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'pages' => $total === 0 ? 0 : (int) ceil($total / $perPage),
            ]);
        } catch (\Throwable $exception) {
            $this->json(['error' => 'Não foi possível listar os favoritos'], 500);
        }
    }

    protected function movieDatabase(): TheMovieDatabase
    {
        return new TheMovieDatabase();
    }

    private function ensureLocalMovie(int $movieId): bool
    {
        try {
            $movie = $this->movieDatabase()->getMovieDetails($movieId);
        } catch (\Throwable $exception) {
            $this->json(['error' => 'Serviço de filmes indisponível'], 502);
            return false;
        }

        if (!is_array($movie)) {
            $this->json(['error' => 'Serviço de filmes indisponível'], 502);
            return false;
        }
        if (($movie['status_code'] ?? null) === 34) {
            $this->json(['error' => 'Filme não encontrado'], 404);
            return false;
        }
        if (!isset($movie['id'])
            || (!is_int($movie['id']) && !ctype_digit((string) $movie['id']))
            || (int) $movie['id'] !== $movieId) {
            $this->json(['error' => 'Serviço de filmes indisponível'], 502);
            return false;
        }

        Movie::saveFromTmdb($movie);
        return true;
    }

    /** @return array<string, mixed> */
    private function requestData(Request $request): array
    {
        $data = json_decode(file_get_contents('php://input'), true);
        return is_array($data) ? $data : $request->getParams();
    }

    private function positiveInteger(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }
        if (!is_string($value) || !preg_match('/^[1-9][0-9]*$/D', $value)) {
            return null;
        }
        if (strlen($value) > strlen((string) PHP_INT_MAX)
            || strlen($value) === strlen((string) PHP_INT_MAX) && strcmp($value, (string) PHP_INT_MAX) > 0) {
            return null;
        }

        return (int) $value;
    }

    private function positiveMovieId(mixed $value): ?int
    {
        $movieId = $this->positiveInteger($value);
        return $movieId !== null && $movieId <= 4294967295 ? $movieId : null;
    }

    private function isDuplicateFavorite(PDOException $exception): bool
    {
        $errorInfo = $exception->errorInfo;
        return ($errorInfo[0] ?? null) === '23000' && (int) ($errorInfo[1] ?? 0) === 1062;
    }

    private function favoriteResponse(int $movieId, int $status): void
    {
        $this->json(['success' => true, 'data' => [
            'movie_id' => $movieId,
            'user_favorited' => true,
        ]], $status);
    }
}
