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
            error_log("DEBUG: iniciando processRating");

            $ratingRecord = MovieRating::findByUserAndMovie($userId, $movieId) ?? new MovieRating();
            $ratingRecord->user_id = $userId;
            $ratingRecord->movie_id = $movieId;
            $ratingRecord->rating = (int)$ratingValue;
            
            error_log("DEBUG: Antes do save, ID atal: " . $ratingRecord->id);
            
            $saved = $ratingRecord->save();
            error_log("DEBUG: Depois do save, ID atual: " . ($saved ? 'true' : 'false') . ". ID após save: " . ($ratingRecord->id ?? 'NULL'));

            if (!$saved) {
                throw new \Exception('Erro ao salvar a avaliação do filme.' . implode(', ', $ratingRecord->errors()));
            }

            if (empty($ratingRecord->id)) {
                error_log("DEBUG: ID da avaliação do filme não definido após salvar.");
                $latest = MovieRating::findBy(['user_id' => $userId, 'movie_id' => $movieId]);
                if ($latest) {
                    $ratingRecord->id = $latest->id;
                    error_log("DEBUG: ID da avaliação do filme recuperado: " . $ratingRecord->id);
                } else {
                    throw new \Exception('Não foi possível recuperar a avaliação do filme após salvar.');
                }
            }

            error_log("DEBUG: Antes de sincronizar");

            MoviesRatingTags::syncForRating($ratingRecord->id, $tagIds);
            error_log("DEBUG: Depois de sincronizar");

            $pdo->commit();
            error_log("DEBUG: Transação concluída com sucesso");
        } catch (\Exception $e) {
            error_log("DEBUG: Exceção capturada: " . $e->getMessage());
            $pdo->rollBack();
            throw $e;
        }
    }
}