<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\TheMovieDatabase;
use Lib\Authentication\Auth;
use App\Models\Movie;
use App\Models\MovieRating;
use Lib\FlashMessage;
use App\Services\MovieRatingService;

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
        $tags = $decode['tags'] ?? $request->getParam('tags') ?? [];
        $userId = $user->id;

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

        try {
            $service = new MovieRatingService();
            $service->processRating($userId, (int)$movieId, (int)$rating, $tags);

            FlashMessage::success('Avaliação salva com sucesso!');
            $this->json([
                'success' => true,
                'message' => 'Avaliação salva com sucesso!',
                'data' => [
                    'movie_id' => $movieId,
                    'rating' => $rating,
                    'tags' => $tags,
                    'average_rating' => MovieRating::getAverageByMovieId($movieId)
                ]
            ], 200);
        } catch (\Exception $e) {
            FlashMessage::danger('Erro ao salvar avaliação!');
            $this->json([
                'success' => false,
                'message' => 'Erro ao salvar avaliação!',
                'error' => [$e->getMessage()]
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
}
