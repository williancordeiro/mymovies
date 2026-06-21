<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\TheMovieDatabase;
use Lib\Authentication\Auth;
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
}