<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;
use Core\Http\Request;

class GalleryController extends Controller
{
    // POST /gallery/images  → adiciona uma imagem
    public function addImage(): void
    {
        $user = $this->currentUser();
        if (!$user) {
            $this->json(['error' => 'Usuário não encontrado'], 401);
            return;
        }

        // valida que o upload chegou de fato (igual updateAvatar faz)
        if (!isset($_FILES['image_file']) || $_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'Arquivo inválido'], 400);
            return;
        }

        // toda a lógica está no service — o controller só chama e responde
        $image = $user->gallery()->add($_FILES['image_file']);

        if ($image) {
            $this->json(['message' => 'Imagem adicionada à galeria', 'id' => $image->id], 201);
        } else {
            $this->json(['errors' => $user->gallery()->errors()], 422);
        }
    }

    // GET /gallery/images  → lista as imagens do usuário
    public function listImages(): void
    {
        $user = $this->currentUser();
        if (!$user) {
            $this->json(['error' => 'Usuário não encontrado'], 401);
            return;
        }

        $this->json([
            'images' => array_map(
                fn($img) => ['id' => $img->id, 'url' => $img->path()],
                $user->gallery()->all()
            ),
        ]);
    }

    // DELETE /gallery/images/{id}  → remove uma imagem
    public function deleteImage(Request $request): void
    {
        $user = $this->currentUser();
        if (!$user) {
            $this->json(['error' => 'Usuário não encontrado'], 401);
            return;
        }

        $id = (int) $request->getParam('id');   // o {id} da URL

        if ($user->gallery()->remove($id)) {
            $this->json(['message' => 'Imagem removida']);
        } else {
            $this->json(['error' => 'Imagem não encontrada'], 404);
        }
    }
}
