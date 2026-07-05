<?php

namespace Lib;

class TheMovieDatabase
{
    private string $apiKey;
    private string $readToken;

    public function __construct()
    {
        $this->apiKey = $_ENV['TMDB_API_KEY'];
        $this->readToken = $_ENV['TMDB_READ_TOKEN'];
    }

    public function getPopularMovies()
    {
        $query = http_build_query([
            'language' => 'pt-BR',
            'page' => 1,
            'sort_by' => 'popularity.desc',
            'include_adult' => 'false',
            'certification_country' => 'US',
            'certification.lte' => 'PG-13',
            'vote_count.gte' => 200,
        ]);
        $url = "https://api.themoviedb.org/3/discover/movie?{$query}";
        $headers = [
            "Authorization: Bearer {$this->readToken}",
            "Accept: application/json"
        ];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    public function getMovieDetails($id)
    {
        $url = "https://api.themoviedb.org/3/movie/{$id}?language=pt-BR";
        $headers = [
            "Authorization: Bearer {$this->readToken}",
            "Accept: application/json"
        ];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    public function searchMovies($query)
    {
        $queryParams = http_build_query([
            'query' => $query,
            'language' => 'pt-BR',
            'page' => 1,
            'include_adult' => 'false'
        ]);
        $url = "https://api.themoviedb.org/3/search/movie?{$queryParams}";

        $headers = [
            "Authorization: Bearer {$this->readToken}",
            "Accept: application/json"
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }
}
