<?php

namespace App\Controllers;

use App\Models\Movie;
use App\Models\Playlist;
use Core\Database\Database;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\TheMovieDatabase;
use PDO;

class PlaylistController extends Controller
{
    public function create(Request $request): void
    {
        $user = $this->currentUser();
        if (!$user) {
            $this->json(['error' => 'Não autorizado'], 401);
            return;
        }

        $data = $this->requestData($request);
        $input = $data === false ? null : $this->playlistInput($data);
        if ($input === null) {
            $this->json(['error' => 'Dados da playlist inválidos'], 400);
            return;
        }

        if (!$this->ensureLocalMovies($input['movie_ids'])) {
            return;
        }

        $pdo = null;
        try {
            $pdo = Database::getDatabaseConn();
            $pdo->beginTransaction();
            $playlist = Playlist::createInTransaction($pdo, (int) $user->id, $input['title']);
            Playlist::replaceMovies($pdo, (int) $playlist['id'], $input['movie_ids']);
            $pdo->commit();
            $this->json(['success' => true, 'data' => $this->metadata($playlist)], 201);
        } catch (\Throwable $exception) {
            if ($pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->json(['error' => 'Não foi possível salvar a playlist'], 500);
        }
    }

    public function index(Request $request): void
    {
        $user = $this->currentUser();
        if (!$user) {
            $this->json(['error' => 'Não autorizado'], 401);
            return;
        }
        $pagination = $this->pagination($request);
        if ($pagination === null) {
            $this->json(['error' => 'page e per_page devem ser inteiros positivos'], 400);
            return;
        }

        try {
            $total = Playlist::countForUser((int) $user->id);
            $rows = Playlist::listForUser((int) $user->id, $pagination['per_page'], $pagination['offset']);
            $this->json([
                'playlists' => array_map(fn (array $playlist): array => $this->metadata($playlist), $rows),
                'total' => $total,
                'page' => $pagination['page'],
                'per_page' => $pagination['per_page'],
                'pages' => $total === 0 ? 0 : (int) ceil($total / $pagination['per_page']),
            ]);
        } catch (\Throwable $exception) {
            $this->json(['error' => 'Não foi possível listar as playlists'], 500);
        }
    }

    public function show(Request $request): void
    {
        $user = $this->currentUser();
        if (!$user) {
            $this->json(['error' => 'Não autorizado'], 401);
            return;
        }
        $playlistId = $this->routeId($request);
        $pagination = $this->pagination($request);
        if ($playlistId === null || $pagination === null) {
            $this->json(['error' => 'Parâmetros inválidos'], 400);
            return;
        }

        try {
            $playlist = Playlist::findOwned($playlistId, (int) $user->id);
            if ($playlist === null) {
                $this->notFound();
                return;
            }
            $total = Playlist::movieCount($playlistId);
            $movies = Playlist::movies($playlistId, $pagination['per_page'], $pagination['offset']);
            $this->json([
                'playlist' => $this->metadata($playlist),
                'movies' => array_map(static fn (array $movie): array => [
                    'movie_id' => (int) $movie['movie_id'],
                    'title' => $movie['title'],
                    'poster_path' => $movie['poster_path'],
                    'release_date' => $movie['release_date'],
                ], $movies),
                'total' => $total,
                'page' => $pagination['page'],
                'per_page' => $pagination['per_page'],
                'pages' => $total === 0 ? 0 : (int) ceil($total / $pagination['per_page']),
            ]);
        } catch (\Throwable $exception) {
            $this->json(['error' => 'Não foi possível consultar a playlist'], 500);
        }
    }

    public function update(Request $request): void
    {
        $user = $this->currentUser();
        if (!$user) {
            $this->json(['error' => 'Não autorizado'], 401);
            return;
        }
        $playlistId = $this->routeId($request);
        $data = $this->requestData($request);
        $input = $data === false ? null : $this->playlistInput($data);
        if ($playlistId === null || $input === null) {
            $this->json(['error' => 'Dados da playlist inválidos'], 400);
            return;
        }

        try {
            if (Playlist::findOwned($playlistId, (int) $user->id) === null) {
                $this->notFound();
                return;
            }
        } catch (\Throwable $exception) {
            $this->json(['error' => 'Não foi possível atualizar a playlist'], 500);
            return;
        }

        if ($input['movie_ids_present'] && !$this->ensureLocalMovies($input['movie_ids'])) {
            return;
        }

        $pdo = null;
        try {
            $pdo = Database::getDatabaseConn();
            $pdo->beginTransaction();
            $playlist = Playlist::findOwned($playlistId, (int) $user->id, $pdo, true);
            if ($playlist === null) {
                $pdo->rollBack();
                $this->notFound();
                return;
            }

            if ($playlist['title'] !== $input['title']) {
                Playlist::updateTitle($pdo, $playlistId, (int) $user->id, $input['title']);
            }
            if ($input['movie_ids_present'] && Playlist::replaceMovies($pdo, $playlistId, $input['movie_ids'])) {
                Playlist::touch($pdo, $playlistId, (int) $user->id);
            }
            $pdo->commit();
            $updated = Playlist::findOwned($playlistId, (int) $user->id);
            if ($updated === null) {
                throw new \RuntimeException('Playlist não encontrada após atualização');
            }
            $this->json(['success' => true, 'data' => $this->metadata($updated)]);
        } catch (\Throwable $exception) {
            if ($pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->json(['error' => 'Não foi possível atualizar a playlist'], 500);
        }
    }

    public function delete(Request $request): void
    {
        $user = $this->currentUser();
        if (!$user) {
            $this->json(['error' => 'Não autorizado'], 401);
            return;
        }
        $playlistId = $this->routeId($request);
        if ($playlistId === null) {
            $this->json(['error' => 'ID da playlist inválido'], 400);
            return;
        }

        $pdo = null;
        try {
            $pdo = Database::getDatabaseConn();
            $pdo->beginTransaction();
            if (Playlist::findOwned($playlistId, (int) $user->id, $pdo, true) === null) {
                $pdo->rollBack();
                $this->notFound();
                return;
            }
            Playlist::deleteOwned($pdo, $playlistId, (int) $user->id);
            $pdo->commit();
            $this->json(['success' => true]);
        } catch (\Throwable $exception) {
            if ($pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->json(['error' => 'Não foi possível remover a playlist'], 500);
        }
    }

    protected function movieDatabase(): TheMovieDatabase
    {
        return new TheMovieDatabase();
    }

    /** @return array<string, mixed>|false */
    private function requestData(Request $request): array|false
    {
        $contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '')[0]));
        $body = file_get_contents('php://input');
        if ($contentType === 'application/json') {
            $data = json_decode($body, true);
            return json_last_error() === JSON_ERROR_NONE && is_array($data) && str_starts_with(ltrim($body), '{') ? $data : false;
        }
        if ($contentType === 'application/x-www-form-urlencoded') {
            parse_str($body, $data);
            return is_array($data) ? $data : false;
        }
        if (str_starts_with($contentType, 'multipart/form-data')) {
            return $_POST !== [] ? $_POST : $request->getParams();
        }
        if ($contentType !== '' || $body !== '') {
            return false;
        }
        return $_POST !== [] ? $_POST : $request->getParams();
    }

    /** @param array<string, mixed> $data
     * @return array{title: string, movie_ids: array<int, int>, movie_ids_present: bool}|null
     */
    private function playlistInput(array $data): ?array
    {
        foreach ($data as $key => $_) {
            if (!is_string($key) || !in_array($key, ['title', 'movie_ids'], true)) {
                return null;
            }
        }
        if (!array_key_exists('title', $data) || !is_string($data['title'])) {
            return null;
        }
        $title = trim($data['title']);
        $length = function_exists('mb_strlen') ? mb_strlen($title) : strlen($title);
        if ($length < 1 || $length > 255) {
            return null;
        }

        $present = array_key_exists('movie_ids', $data);
        if (!$present) {
            return ['title' => $title, 'movie_ids' => [], 'movie_ids_present' => false];
        }
        if (!is_array($data['movie_ids'])) {
            return null;
        }
        $movieIds = [];
        foreach ($data['movie_ids'] as $movieId) {
            $movieId = $this->positiveUnsignedId($movieId);
            if ($movieId === null || isset($movieIds[$movieId])) {
                return null;
            }
            $movieIds[$movieId] = $movieId;
        }
        return ['title' => $title, 'movie_ids' => array_values($movieIds), 'movie_ids_present' => true];
    }

    /** @param array<int, int> $movieIds */
    private function ensureLocalMovies(array $movieIds): bool
    {
        foreach ($movieIds as $movieId) {
            try {
                $localMovie = Movie::findById($movieId);
            } catch (\Throwable $exception) {
                $this->json(['error' => 'Não foi possível salvar a playlist'], 500);
                return false;
            }
            if ($localMovie) {
                continue;
            }
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
            if ($this->positiveUnsignedId($movie['id'] ?? null) !== $movieId) {
                $this->json(['error' => 'Serviço de filmes indisponível'], 502);
                return false;
            }
            try {
                Movie::saveFromTmdb($movie);
            } catch (\Throwable $exception) {
                $this->json(['error' => 'Não foi possível salvar a playlist'], 500);
                return false;
            }
        }
        return true;
    }

    /** @return array{page: int, per_page: int, offset: int}|null */
    private function pagination(Request $request): ?array
    {
        $query = [];
        parse_str((string) parse_url($request->getUri(), PHP_URL_QUERY), $query);
        $page = $this->positiveInteger($query['page'] ?? 1);
        $perPage = $this->positiveInteger($query['per_page'] ?? 20);
        if ($page === null || $perPage === null) {
            return null;
        }
        $perPage = min($perPage, 100);
        if ($page > intdiv(PHP_INT_MAX, $perPage) + 1) {
            return null;
        }
        return ['page' => $page, 'per_page' => $perPage, 'offset' => ($page - 1) * $perPage];
    }

    private function routeId(Request $request): ?int
    {
        $path = (string) parse_url($request->getUri(), PHP_URL_PATH);
        return $this->positiveUnsignedId(basename($path));
    }

    private function positiveInteger(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }
        if (!is_string($value) || !preg_match('/^[1-9][0-9]*$/D', $value)) {
            return null;
        }
        $max = (string) PHP_INT_MAX;
        if (strlen($value) > strlen($max) || (strlen($value) === strlen($max) && strcmp($value, $max) > 0)) {
            return null;
        }
        return (int) $value;
    }

    private function positiveUnsignedId(mixed $value): ?int
    {
        $id = $this->positiveInteger($value);
        return $id !== null && $id <= 4294967295 ? $id : null;
    }

    /** @param array<string, mixed> $playlist
     * @return array{id: int, title: string, created_at: mixed, updated_at: mixed}
     */
    private function metadata(array $playlist): array
    {
        return [
            'id' => (int) $playlist['id'],
            'title' => $playlist['title'],
            'created_at' => $playlist['created_at'],
            'updated_at' => $playlist['updated_at'],
        ];
    }

    private function notFound(): void
    {
        $this->json(['error' => 'Playlist não encontrada'], 404);
    }
}
