<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\TheMovieDatabase;
use Lib\Authentication\Auth;
use App\Models\Movie;
use App\Models\MovieRating;
use Lib\FlashMessage;
use App\Models\MovieRatingTag;
use App\Models\RatingTag;

class MoviesController extends Controller
{

    public function tags(): void
    {
        $tags = RatingTag::all();
        $this->json([
            'tags' => array_map(fn($t) => ['id' => $t->id, 'name' => $t->name], $tags)
        ]);
    }

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

        $tags = $decode['tags'] ?? [];

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
            foreach ($tags as $tagName) {
                $tagName = trim((string) $tagName);
                if ($tagName === '') continue;

                $tag = RatingTag::findBy(['name' => $tagName]);
                if (!$tag) {
                    $tag = new RatingTag(['name' => $tagName]);
                    $tag->save();
                }

                $exists = MovieRatingTag::findBy([
                    'movie_rating_id' => $ratingRecord->id,
                    'rating_tag_id'   => $tag->id,
                ]);

                if (!$exists) {
                    $pivot = new MovieRatingTag([
                        'movie_rating_id' => $ratingRecord->id,
                        'rating_tag_id'   => $tag->id,
                    ]);
                    $pivot->save();
                }
            }
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
}
