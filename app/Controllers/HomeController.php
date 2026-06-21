<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\TheMovieDatabase;
use App\Models\MovieRating;

class HomeController extends Controller
{
    public function index(Request $request): void
    {
        $tmdb = new TheMovieDatabase();

        $data = $tmdb->getPopularMovies();

        foreach ($data['results'] as &$movie) {
            $movieId = $movie['id'];
            $movie['mymovies_rating_average'] = MovieRating::getAverageByMovieId($movieId);
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

        $movie['mymovies_rating_average'] = MovieRating::getAverageByMovieId($id);

        $user = $this->currentUser();
        if ($user) {
            $userRating = MovieRating::findBy(['user_id' => $user->id, 'movie_id' => $id]);
            $movie['user_rating'] = $userRating ? $userRating->rating : null;
        }

        $this->json([
            'movie' => $movie
        ]);
    }
}
