<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\TheMovieDatabase;
use Lib\Authentication\Auth;
use App\Models\Movie;
use App\Models\MovieRating;
use Lib\FlashMessage;

class MoviesController extends Controller
{
    public function rate(Request $request): void
    {
        $user = $this->currentUser();
        if (!$user) {
            $this->json(['error' => 'Não autorizado'], 401);
            return;
        }

        $json = file_get_contents('php://input');
        $decode = json_decode($json, true);

        $movieId = $decode['movie_id'] ?? $request->getParam('movie_id');
        $rating = $decode['rating'] ?? $request->getParam('rating');

        if (!$movieId || !$rating) {
            $this->json(['error' => 'Dados incompletos'], 400);
            return;
        }

        // Salva o filme localmente se ainda não existir
        $existingMovie = Movie::findById((int) $movieId);
        if (!$existingMovie) {
            $tmdb = new TheMovieDatabase();
            $movieData = $tmdb->getMovieDetails($movieId);
            if ($movieData) {
                Movie::saveFromTmdb($movieData);
            }
        }

        $ratingRecord = MovieRating::findBy(['user_id' => $user->id, 'movie_id' => $movieId]);

        if ($ratingRecord) {
            $ratingRecord->rating = (int)$rating;
        } else {
            $ratingRecord = new MovieRating([
                'user_id' => $user->id,
                'movie_id' => $movieId,
                'rating' => (int)$rating
            ]);
        }

        if ($ratingRecord->save()) {
            FlashMessage::success('Avaliação salva com sucesso!');
            $this->json([
                'success' => true,
                'message' => 'Avaliação salva com sucesso!',
                'data' => [
                    'movie_id' => $movieId,
                    'rating' => (int)$rating,
                    'average_rating' => MovieRating::getAverageByMovieId($movieId)
                ]
            ], 200);
        } else {
            FlashMessage::danger('Erro ao salvar avaliação!');
            $this->json([
                'success' => false,
                'message' => 'Erro ao salvar avaliação!',
                'errors' => $ratingRecord->errors()
            ], 500);
        }
    }

    public function unrate(Request $request): void
    {
        $user = $this->currentUser();
        if (!$user) {
            $this->json(['error' => 'Não autorizado'], 401);
            return;
        }

        $movieId = $request->getParam('movie_id');

        $rating = MovieRating::findBy(['user_id' => $user->id, 'movie_id' => $movieId]);

        if (!$rating) {
            $this->json(['error' => 'Avaliação não encontrada'], 404);
            return;
        }

        if ($rating->destroy()) {
            $this->json(['success' => true, 'message' => 'Avaliação removida!']);
        } else {
            $this->json(['error' => 'Erro ao remover avaliação'], 500);
        }
    }

    public function search(Request $request): void
    {
        $query = $request->getParam('q');

        if (!$query || strlen($query) < 3) {
            $this->json(['error' => 'A consulta deve ter pelo menos 1 caracteres'], 400);
            return;
        }

        $tmdb = new TheMovieDatabase();
        $data = $tmdb->searchMovies($query);

        $movies = $data['results'] ?? [];

        $movies = array_slice($movies, 0, 30);

        foreach ($movies as &$movie) {
            $movieId = $movie['id'];
            $movie['mymovies_rating_average'] = MovieRating::getAverageByMovieId($movieId);
            
            $user = $this->currentUser();
            if ($user) {
                $userRating = MovieRating::findBy(['user_id' => $user->id, 'movie_id' => $movieId]);
                $movie['user_rating'] = $userRating ? $userRating->rating : null;
            }
        }

        $this->json(['results' => $movies]);
    }
}
