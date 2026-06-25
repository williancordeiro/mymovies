<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\TheMovieDatabase;
use App\Models\Movie;
use App\Models\MovieRating;

class HomeController extends Controller
{
    public function index(Request $request): void
    {
        $tmdb = new TheMovieDatabase();
        $data = $tmdb->getPopularMovies();
        $user = $this->currentUser();

        // Fallback offline: se o TMDB não respondeu, usa os filmes do banco local.
        if (!is_array($data) || empty($data['results'])) {
            $data = ['results' => array_map(
                fn(Movie $m) => self::movieToArray($m),
                Movie::all()
            )];
        }

        foreach ($data['results'] as &$movie) {
            $movieId = $movie['id'];
            $movie['mymovies_rating_average'] = MovieRating::getAverageByMovieId($movieId);

            if ($user) {
                $userRating = MovieRating::findBy(['user_id' => $user->id, 'movie_id' => $movieId]);
                $movie['user_rating'] = $userRating ? $userRating->rating : null;
            } else {
                $movie['user_rating'] = null;
            }
        }

        $this->json([
            'movies' => $data
        ]);
    }

    /*public function rate(Request $request): void
    {
        $user = $this->currentUser();

        if (!$user) {
            $this->json(['error' => 'Não autorizado'], 401);
            return;
        }

        // Lê o corpo da requisição JSON do Angular
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $movieId = $data['movie_id'] ?? $request->getParam('movie_id');
        $rating = $data['rating'] ?? $request->getParam('rating');

        if (!$movieId || !$rating) {
            $this->json(['error' => 'Dados incompletos', 'received' => $data], 400);
            return;
        }

        $db = \Core\Database\Database::getDatabaseConn();

        $query = "INSERT INTO movies_rating (user_id, movie_id, rating)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE rating = VALUES(rating)";

        $stmt = $db->prepare($query);
        $result = $stmt->execute([$user->id, $movieId, $rating]);

        $this->json([
            'success' => $result,
            'message' => 'Avaliação salva com sucesso!',
            'rating' => $rating
        ]);
    }*/


    public function show(Request $request): void
    {
        $id = (int)$request->getParam('id');
        $tmdb = new TheMovieDatabase();
        $movie = $tmdb->getMovieDetails($id);

        // Fallback offline: se o TMDB não respondeu, usa o filme do banco local.
        if (!is_array($movie) || empty($movie['id'])) {
            $localMovie = Movie::findById($id);

            if (!$localMovie) {
                $this->json(['error' => 'Filme não encontrado'], 404);
                return;
            }

            $movie = self::movieToArray($localMovie);
        }

        $movie['mymovies_rating_average'] = MovieRating::getAverageByMovieId($id);

        $user = $this->currentUser();
        $userRating = null;
        if ($user) {
            $ratingRecord = MovieRating::findBy(['user_id' => $user->id, 'movie_id' => $id]);
            $userRating = $ratingRecord ? $ratingRecord->rating : null;
        }
        $movie['user_rating'] = $userRating;

        $this->json([
            'movie' => $movie
        ]);
    }

    /**
     * Converte um Movie do banco local no mesmo formato esperado pelo frontend
     * (mesma estrutura que o TMDB retorna). Campos exclusivos do TMDB
     * (genres, runtime, tagline, backdrop_path) ficam vazios offline.
     *
     * @return array<string, mixed>
     */
    private static function movieToArray(Movie $movie): array
    {
        return [
            'id'            => $movie->id,
            'title'         => $movie->title,
            'overview'      => $movie->overview,
            'poster_path'   => $movie->poster_path,
            'backdrop_path' => null,
            'release_date'  => $movie->release_date,
            'vote_average'  => $movie->vote_average,
            'genres'        => [],
            'runtime'       => null,
            'tagline'       => null,
        ];
    }
}
