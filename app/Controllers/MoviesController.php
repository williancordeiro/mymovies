<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\TheMovieDatabase;
use App\Models\MovieRatingTag;
use Lib\Authentication\Auth;
use App\Models\RatingTag;
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
        $tagIds = $decode['tag_ids'] ?? [];

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
    // Associa as tags à avaliação
            foreach ($tagIds as $tagId) {
                $existing = MovieRatingTag::findBy([
                    'movie_rating_id' => $ratingRecord->id,
                    'rating_tag_id' => $tagId
                ]);
                if (!$existing) {
                    $movieRatingTag = new MovieRatingTag([
                        'movie_rating_id' => $ratingRecord->id,
                        'rating_tag_id' => $tagId
                    ]);
                    $movieRatingTag->save();
                }
            }

            $this->json([
            'success' => true,
            'message' => 'Avaliação salva com sucesso!',
            'data' => [
            'movie_id' => $movieId,
            'rating' => (int)$rating,
            'tags' => array_map(fn($tagId) => [
            'id' => $tagId,
            'tag' => RatingTag::findById($tagId)->tag ?? null
        ], $tagIds),
            'average_rating' => MovieRating::getAverageByMovieId($movieId)
            ]
            ], 200);
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

    public function listTags(Request $request): void
    {
        $tags = RatingTag::all();
        $this->json([
        'tags' => array_map(fn($tag) => [
            'id' => $tag->id,
            'tag' => $tag->tag
        ], $tags)
        ]);
    }
}
