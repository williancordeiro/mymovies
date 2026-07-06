<?php

namespace App\Services;

use Core\Constants\Constants;
use Core\Database\ActiveRecord\Model;
use App\Models\MovieRating;
use App\Models\MoviesRatingTags;
use Core\Database\Database;
use PDO;

class MovieRatingService
{
    public function processRating(int $userId, int $movieId, int $ratingValue, array $tagIds): void
    {
        $pdo = Database::getDatabaseConn();
        $pdo->beginTransaction();

        try {

            $ratingRecord = MovieRating::findByUserAndMovie($userId, $movieId) ?? new MovieRating();
            $ratingRecord->user_id = $userId;
            $ratingRecord->movie_id = $movieId;
            $ratingRecord->rating = (int)$ratingValue;
            
            $saved = $ratingRecord->save();

            if (!$saved) {
                throw new \Exception('Erro ao salvar a avaliação do filme.' . implode(', ', $ratingRecord->errors()));
            }

            if (empty($ratingRecord->id)) {
                $latest = MovieRating::findBy(['user_id' => $userId, 'movie_id' => $movieId]);
                if ($latest) {
                    $ratingRecord->id = $latest->id;
                } else {
                    throw new \Exception('Não foi possível recuperar a avaliação do filme após salvar.');
                }
            }

            MoviesRatingTags::syncForRating($ratingRecord->id, $tagIds);

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}