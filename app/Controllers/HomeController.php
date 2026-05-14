<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\TheMovieDatabase;

class HomeController extends Controller
{
    public function index(Request $request): void
    {
        $tmdb = new TheMovieDatabase();

        $popularMovies = $tmdb->getPopularMovies();

        $this->json(['movies' => $popularMovies]);
    }
}