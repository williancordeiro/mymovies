<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\TheMovieDatabase;

class HomeController extends Controller
{
    public function index(Request $request): void
    {
        $tmdb = new TheMovieDatabase();

        $popularMovies = $tmdb->getPopularMovies();

        $this->json(['movies' => $popularMovies]);
    }

    public function rate(Request $request) {
        $user = $this->currentUser();
        
        if (!$user) {
            return $this->json(['error' => 'Não autorizado'], 401);
        }

        // Lê o corpo da requisição JSON do Angular
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $movieId = $data['movie_id'] ?? $request->getParam('movie_id');
        $rating = $data['rating'] ?? $request->getParam('rating');

        if (!$movieId || !$rating) {
            return $this->json(['error' => 'Dados incompletos', 'received' => $data], 400);
        }

        $db = \Core\Database\Database::getDatabaseConn();
        
        $query = "INSERT INTO movie_ratings (user_id, movie_id, rating) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE rating = VALUES(rating)";
                
        $stmt = $db->prepare($query);
        $result = $stmt->execute([$user->id, $movieId, $rating]);

        return $this->json([
            'success' => $result,
            'message' => 'Avaliação salva com sucesso!',
            'rating' => $rating
        ]);
    }


    public function show(Request $request) {
        $id = $request->getParam('id'); 
        
        $tmdb = new TheMovieDatabase();
        $movie = $tmdb->getMovieDetails($id);
        
        $this->json(['movie' => $movie]);
    }
}