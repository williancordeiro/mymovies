<?php

namespace Tests\Unit\Controllers;

use App\Controllers\PlaylistController;
use App\Models\Movie;
use App\Models\MovieRating;
use App\Models\Playlist;
use App\Models\User;
use Core\Http\Request;
use Lib\Authentication\Auth;
use Lib\TheMovieDatabase;
use Tests\TestCase;

class PlaylistControllerTest extends TestCase
{
    private User $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = new User(['username' => 'controller', 'handle' => 'controller', 'email' => 'controller@example.test', 'password' => 'password123', 'role' => 'Default']);
        $this->user->save();
        Movie::saveFromTmdb(['id' => 101, 'title' => 'Local']);
    }

    public function test_creates_and_updates_using_form_data_without_calling_tmdb_for_local_movies(): void
    {
        $tmdb = new PlaylistFakeTmdb([]);
        $created = $this->call('create', '/playlists', ['title' => '  Minha lista  ', 'movie_ids' => ['101']], $tmdb);
        $playlist = json_decode($created, true)['data'];
        $updated = json_decode($this->call('update', '/playlists/' . $playlist['id'], ['title' => 'Renomeada'], $tmdb), true);

        $this->assertSame('Minha lista', $playlist['title']);
        $this->assertTrue($updated['success']);
        $this->assertSame([], $tmdb->requested);
        $this->assertSame(1, Playlist::movieCount($playlist['id']));
    }

    public function test_rejects_invalid_payloads_and_route_ids_before_writing(): void
    {
        foreach (self::invalidPayloads() as $payload) {
            $response = json_decode($this->call('create', '/playlists', $payload, new PlaylistFakeTmdb([])), true);
            $this->assertSame('Dados da playlist inválidos', $response['error']);
        }
        $response = json_decode($this->call('show', '/playlists/1.5', [], new PlaylistFakeTmdb([])), true);
        $this->assertSame('Parâmetros inválidos', $response['error']);
        $this->assertSame(0, Playlist::countForUser($this->user->id));
    }

    /** @return array<int, array<string, mixed>> */
    public static function invalidPayloads(): array
    {
        return [
            [], ['title' => null], ['title' => 1], ['title' => ''], ['title' => " \t "], ['title' => str_repeat('a', 256)],
            ['title' => 'x', 'movie_ids' => null], ['title' => 'x', 'movie_ids' => '101'], ['title' => 'x', 'movie_ids' => [true]],
            ['title' => 'x', 'movie_ids' => [1.5]], ['title' => 'x', 'movie_ids' => ['1e2']], ['title' => 'x', 'movie_ids' => ['01']],
            ['title' => 'x', 'movie_ids' => [0]], ['title' => 'x', 'movie_ids' => [-1]], ['title' => 'x', 'movie_ids' => ['4294967296']],
            ['title' => 'x', 'movie_ids' => [1, '1']], ['title' => 'x', 'movie_ids' => [[]]], ['title' => 'x', 'movie_ids' => [new \stdClass()]],
            ['title' => 'x', 'user_id' => 99], ['title' => 'x', 'id' => 99], ['title' => 'x', 'created_at' => 'now'],
        ];
    }

    public function test_remote_failures_do_not_leave_a_partial_playlist_and_remote_movie_is_cached(): void
    {
        $failure = json_decode($this->call('create', '/playlists', ['title' => 'Falha', 'movie_ids' => [777]], new PlaylistFakeTmdb([777 => null])), true);
        $this->assertSame('Serviço de filmes indisponível', $failure['error']);
        $this->assertSame(0, Playlist::countForUser($this->user->id));

        $success = json_decode($this->call('create', '/playlists', ['title' => 'Remota', 'movie_ids' => [888]], new PlaylistFakeTmdb([888 => ['id' => 888, 'title' => 'Remota']])), true);
        $this->assertTrue($success['success']);
        $this->assertNotNull(Movie::findById(888));
    }

    public function test_handles_tmdb_not_found_and_mismatched_ids_without_writing(): void
    {
        $notFound = json_decode($this->call('create', '/playlists', ['title' => 'Ausente', 'movie_ids' => [777]], new PlaylistFakeTmdb([777 => ['status_code' => 34]])), true);
        $mismatch = json_decode($this->call('create', '/playlists', ['title' => 'Divergente', 'movie_ids' => [778]], new PlaylistFakeTmdb([778 => ['id' => 779, 'title' => 'Outro']])), true);
        $this->assertSame('Filme não encontrado', $notFound['error']);
        $this->assertSame('Serviço de filmes indisponível', $mismatch['error']);
        $this->assertSame(0, Playlist::countForUser($this->user->id));
    }

    public function test_update_omits_preserves_replaces_clears_and_is_idempotent(): void
    {
        $tmdb = new PlaylistFakeTmdb([]);
        $id = json_decode($this->call('create', '/playlists', ['title' => 'Fluxo', 'movie_ids' => [101]], $tmdb), true)['data']['id'];
        $this->call('update', '/playlists/' . $id, ['title' => 'Mesmo'], $tmdb);
        $this->assertSame([101], array_column(Playlist::movies($id, 10, 0), 'movie_id'));
        $this->call('update', '/playlists/' . $id, ['title' => 'Mesmo', 'movie_ids' => [101]], $tmdb);
        $this->assertSame([101], array_column(Playlist::movies($id, 10, 0), 'movie_id'));
        $this->call('update', '/playlists/' . $id, ['title' => 'Mesmo', 'movie_ids' => []], $tmdb);
        $this->assertSame(0, Playlist::movieCount($id));
    }

    public function test_rejects_invalid_pagination_including_offset_overflow(): void
    {
        foreach (['?page=0', '?page=1.5', '?page=1e2', '?page=9223372036854775810', '?page=9223372036854775807&per_page=2', '?per_page=0'] as $query) {
            $response = json_decode($this->call('index', '/playlists' . $query, [], new PlaylistFakeTmdb([])), true);
            $this->assertSame('page e per_page devem ser inteiros positivos', $response['error']);
        }
    }

    public function test_show_includes_current_user_rating_and_movie_rating_average(): void
    {
        Movie::saveFromTmdb(['id' => 102, 'title' => 'Sem avaliações']);
        $playlistId = json_decode(
            $this->call('create', '/playlists', ['title' => 'Avaliações', 'movie_ids' => [101, 102]], new PlaylistFakeTmdb([])),
            true
        )['data']['id'];

        (new MovieRating(['user_id' => $this->user->id, 'movie_id' => 101, 'rating' => 4]))->save();
        $otherUser = new User(['username' => 'other', 'handle' => 'other', 'email' => 'other@example.test', 'password' => 'password123', 'role' => 'Default']);
        $otherUser->save();
        (new MovieRating(['user_id' => $otherUser->id, 'movie_id' => 101, 'rating' => 2]))->save();

        $response = json_decode($this->call('show', '/playlists/' . $playlistId, [], new PlaylistFakeTmdb([])), true);
        $movies = array_column($response['movies'], null, 'movie_id');

        $this->assertSame(4, $movies[101]['user_rating']);
        $this->assertSame(3.0, $movies[101]['mymovies_rating_average']);
        $this->assertNull($movies[102]['user_rating']);
        $this->assertSame(0.0, $movies[102]['mymovies_rating_average']);
    }

    private function call(string $action, string $uri, array $params, TheMovieDatabase $tmdb): string
    {
        $_SERVER['REQUEST_METHOD'] = $action === 'create' ? 'POST' : ($action === 'update' ? 'PUT' : 'GET');
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . Auth::generateToken($this->user);
        unset($_SERVER['CONTENT_TYPE'], $_SERVER['HTTP_CONTENT_TYPE']);
        $_REQUEST = $params;
        $controller = new class ($tmdb) extends PlaylistController {
            public function __construct(private TheMovieDatabase $tmdb) {}
            protected function movieDatabase(): TheMovieDatabase { return $this->tmdb; }
        };
        return $this->getOutput(fn () => $controller->$action(new Request()));
    }
}

class PlaylistFakeTmdb extends TheMovieDatabase
{
    /** @param array<int, array<string, mixed>|null> $responses */
    public function __construct(private array $responses, public array $requested = []) {}
    public function getMovieDetails($id) { $this->requested[] = $id; return $this->responses[$id] ?? null; }
}
