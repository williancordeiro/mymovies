<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\Authentication\Auth;
use Lib\FlashMessage;
use App\Models\User;
use App\Services\ProfileImages;

class ProfileController extends Controller
{
    public function update(Request $request): void
    {
        $user = $this->currentUser();
        if (!$user) {
            $this->json(['error' => 'Usuário não encontrado'], 401);
            return;
        }

        $json = file_get_contents('php://input');
        $decode = json_decode($json, true);

        $data = [];

        if (isset($decode['username']) || $request->getParam('username')) {
            $data['username'] = $decode['username'] ?? $request->getParam('username');
        }

        if (isset($decode['handle']) || $request->getParam('handle')) {
            $data['handle'] = $decode['handle'] ?? $request->getParam('handle');
        }

        if (empty($data)) {
            FlashMessage::warning('Nenhum dado foi enviado para atualização');
            $this->json(['error' => 'Nenhum dado foi enviado para atualização'], 400);
            return;
        }

        foreach ($data as $key => $value) {
            $user->$key = $value;
        }

        if ($user->isValid()) {
            if ($user->update($data)) {
                $token = Auth::generateToken($user);
                FlashMessage::success('Dados atualizados com sucesso');
                $this->json([
                    'token' => $token,
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'handle' => $user->handle,
                        'email' => $user->email,
                        'role' => $user->role,
                        'avatar_file' => $user->getAvatarPath(),
                        'banner_file' => $user->getBannerPath()
                    ]
                ]);
            } else {
                FlashMessage::warning('Nenhuma alteração foi realizada');
                $this->json(['error' => 'Nenhuma alteração foi realizada'], 200);
            }
        } else {
            $this->json([
                'message' => 'Erro na validação dos dados',
                'errors' => $user->errors()
            ], 422);
        }
    }

    public function updateAvatar(): void
    {
        $user = $this->currentUser();
        if (!$user) {
            $this->json(['error' => 'Usuário não encontrado'], 401);
            return;
        }

        $image = $_FILES['avatar_file'];
        if (!$image || $image['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'Arquivo inválido'], 400);
            return;
        }

        $service = new ProfileImages($user, [
            'extensions' => ['jpg', 'jpeg', 'png'],
            'max_size' => 2 * 1024 * 1024,
            'aspect_ratio' => [
                'min' => 0.95,
                'max' => 1.05,
            ],
        ], 'avatar_file');

        if ($service->update($image)) {
            $token = Auth::generateToken($user);
            FlashMessage::success('Ícone de perfil atualizado com sucesso!');
            $this->json([
                'message' => 'Ícone atualizado com sucesso',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'handle' => $user->handle,
                    'email' => $user->email,
                    'role' => $user->role,
                    'avatar_file' => $user->getAvatarPath(),
                    'banner_file' => $user->getBannerPath()
                ]
            ]);
        } else {
            $this->json([
                'message' => 'Erro ao atualizar o ícone',
                'errors' => $user->errors()
            ], 422);

        }
    }

    public function updateBanner(): void
    {
        $user = $this->currentUser();
        if (!$user) {
            $this->json(['error' => 'Usuário não encontrado'], 401);
            return;
        }

        $image = $_FILES['banner_file'];
        if (!$image || $image['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'Arquivo inválido'], 400);
            return;
        }

        $service = new ProfileImages($user, [
            'extensions' => ['jpg', 'jpeg', 'png'],
            'max_size' => 5 * 1024 * 1024,
            'aspect_ratio' => [
                'min' => 1.7,
                'max' => 1.8,
            ],
        ], 'banner_file');

        if ($service->update($image)) {
            $token = Auth::generateToken($user);
            FlashMessage::success('Banner atualizado com sucesso!');
            $this->json([
                'message' => 'Banner atualizado com sucesso',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'handle' => $user->handle,
                    'email' => $user->email,
                    'role' => $user->role,
                    'avatar_file' => $user->getAvatarPath(),
                    'banner_file' => $user->getBannerPath()
                ]
            ]);
        } else {
            $this->json([
                'message' => 'Erro ao atualizar o banner',
                'errors' => $user->errors()
            ], 422);

        }
    }
}
?>