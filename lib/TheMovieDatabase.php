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
        $url = "https://api.themoviedb.org/3/movie/popular?language=en-US&page=1";
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
}
