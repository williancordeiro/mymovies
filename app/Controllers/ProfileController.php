<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;
use Lib\Authentication\Auth;
use App\Models\User;
use App\Services\ProfileImages;

class ProfileController extends Controller
{

    public function updateAvatar(): void
    {
        $image = $_FILES['avatar_file'];
        if (!$image || $image['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'Arquivo inválido'], 400);
            return;
        }

        $service = new ProfileImages($this->currentUser(), [
            'extensions' => ['jpg', 'jpeg', 'png'],
            'max_size' => 2 * 1024 * 1024,
        ]);

        if ($service->update($image)) {
            $token = Auth::generateToken($this->currentUser());
            $this->json([
                'message' => 'Ícone atualizado com sucesso',
                'token' => $token,
                'user' => [
                    'id' => $this->currentUser()->id,
                    'username' => $this->currentUser()->username,
                    'handle' => $this->currentUser()->handle,
                    'email' => $this->currentUser()->email,
                    'role' => $this->currentUser()->role,
                    'avatar_file' => $this->currentUser()->getAvatarPath(),
                ]
            ]);
        } else {
            $this->json([
                'message' => 'Erro ao atualizar o ícone',
                'errors' => $this->currentUser()->errors()
            ], 422);

        }
    }
}
?>