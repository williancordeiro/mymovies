<?php

namespace Database\Populate;

use App\Models\Movie;
use App\Models\MovieRating;
use Lib\TheMovieDatabase;

class MovieRatingsPopulate {

    private const SEED_FILE = __DIR__ . '/../seeds/movies.json';

    public static function populate(): void {

        $tmdb = new TheMovieDatabase();

        $movieIds = [936075, 1275779, 1226863, 1228710, 1007757, 687163, 454639, 83533, 862];

        $seed = [];
        if (file_exists(self::SEED_FILE)) {
            foreach (json_decode(file_get_contents(self::SEED_FILE), true) ?? [] as $m) {
                $seed[$m['id']] = $m;
            }
        }

        foreach ($movieIds as $id) {
            $data = $seed[$id] ?? null;
            if (!$data) {
                $tmdbData = $tmdb->getMovieDetails($id);
                if (is_array($tmdbData) && !empty($tmdbData['id'])) {
                    $data = $tmdbData;
                }
            }

            if ($data && isset($data['id'])) {
                Movie::saveFromTmdb($data);
            } else {
                die("Filme {$id} indisponível: sem internet e sem cache em " . self::SEED_FILE . "\n");
            }
        }

        $ratings = [
            ['user_id' => 1, 'movie_id' => 936075,  'rating' => 4],
            ['user_id' => 1, 'movie_id' => 1275779,  'rating' => 3],
            ['user_id' => 1, 'movie_id' => 1226863, 'rating' => 5],
            ['user_id' => 1, 'movie_id' => 862,     'rating' => 5],

            ['user_id' => 2, 'movie_id' => 936075,  'rating' => 2],
            ['user_id' => 2, 'movie_id' => 1228710, 'rating' => 5],
            ['user_id' => 2, 'movie_id' => 83533,   'rating' => 3],
            ['user_id' => 2, 'movie_id' => 862,     'rating' => 4],

            ['user_id' => 3, 'movie_id' => 1007757, 'rating' => 4],
            ['user_id' => 3, 'movie_id' => 687163,  'rating' => 2],
            ['user_id' => 3, 'movie_id' => 454639,  'rating' => 1],
            ['user_id' => 3, 'movie_id' => 936075,  'rating' => 5],
        ];

        foreach ($ratings as $data) {
            $rating = new MovieRating($data);
            if (!$rating->save()) {
                print_r($rating->errors());
                die("Erro ao salvar rating user_id={$data['user_id']} movie_id={$data['movie_id']}");
            }
        }

        echo "Filmes e ratings populados com sucesso.\n";
    }
}
