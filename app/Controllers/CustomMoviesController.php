<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;
use Core\Http\Request;
use App\Models\CustomMovie;

class CustomMoviesController extends Controller
{
    public function index(Request $request): void
    {
        $user = $this->currentUser();
        if (!$user) {
            $this->json(['error' => 'Não autorizado'], 401);
            return;
        }

        $movies = CustomMovie::where(['user_id' => $user->id]);
        $this->json(['movies' => $movies]);
    }

    public function create(Request $request): void
    {
        $user = $this->currentUser();
        if (!$user) {
            $this->json(['error' => 'Não autorizado'], 401);
            return;
        }

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $movie = new CustomMovie();
        $movie->user_id = $user->id;
        $movie->title = !empty($data['title']) ? $data['title'] : null;
        $movie->description = !empty($data['description']) ? $data['description'] : null;
        $movie->release_year = !empty($data['release_year']) ? (int)$data['release_year'] : null;
        $movie->poster_url = !empty($data['poster_url']) ? $data['poster_url'] : null;
        $movie->rating = !empty($data['rating']) ? (int)$data['rating'] : null;
        $movie->status = !empty($data['status']) ? $data['status'] : null;

        if ($movie->save()) {
            $this->json(['movie' => $movie], 201);
        } else {
            $this->json(['errors' => $movie->errors()], 422);
        }
    }

    public function update(Request $request): void
    {
        $user = $this->currentUser();
        if (!$user) {
            $this->json(['error' => 'Não autorizado'], 401);
            return;
        }

        $id = $request->getParam('id');
        $movie = CustomMovie::findById($id);

        if (!$movie || $movie->user_id !== $user->id) {
            $this->json(['error' => 'Filme não encontrado ou sem permissão'], 404);
            return;
        }

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (array_key_exists('title', $data)) $movie->title = !empty($data['title']) ? $data['title'] : null;
        if (array_key_exists('description', $data)) $movie->description = !empty($data['description']) ? $data['description'] : null;
        if (array_key_exists('release_year', $data)) $movie->release_year = !empty($data['release_year']) ? (int)$data['release_year'] : null;
        if (array_key_exists('poster_url', $data)) $movie->poster_url = !empty($data['poster_url']) ? $data['poster_url'] : null;
        if (array_key_exists('rating', $data)) $movie->rating = !empty($data['rating']) ? (int)$data['rating'] : null;
        if (array_key_exists('status', $data)) $movie->status = !empty($data['status']) ? $data['status'] : null;

        if ($movie->save()) {
            $this->json(['movie' => $movie]);
        } else {
            $this->json(['errors' => $movie->errors()], 422);
        }
    }

    public function delete(Request $request): void
    {
        $user = $this->currentUser();
        if (!$user) {
            $this->json(['error' => 'Não autorizado'], 401);
            return;
        }

        $id = $request->getParam('id');
        $movie = CustomMovie::findById($id);

        if (!$movie || $movie->user_id !== $user->id) {
            $this->json(['error' => 'Filme não encontrado ou sem permissão'], 404);
            return;
        }

        $movie->destroy();
        $this->json(['message' => 'Filme excluído com sucesso']);
    }
}
