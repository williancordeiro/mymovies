<?php

namespace Database\Populate;

use App\Models\MovieRating;

class MovieRatingsPopulate {

    public static function populate(): void {

        $rating1 = new MovieRating([
            'user_id' => 1,
            'movie_id' => 1,
            'rating' => 5
        ]);

        $rating1->save();

        $rating2 = new MovieRating([
            'user_id' => 2,
            'movie_id' => 1,
            'rating' => 4
        ]);

        $rating2->save();

        echo "Filmes populados com sucesso.\n";
    }
}