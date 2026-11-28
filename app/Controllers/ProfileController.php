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

    public function show(Request $request): void
    {
        $user = $this->currentUser();
        if (!$user) {
            $this->json(['error' => 'Usuário não encontrado'], 401);
            return;
        }
        $this->json([
        'id'          => $user->id,
        'username'    => $user->username,
        'handle'      => $user->handle,
        'email'       => $user->email,
        'role'        => $user->role,
        'avatar_file' => $user->getAvatarPath(),
        'banner_file' => $user->getBannerPath()
        ]);
    }

    public function deleteAvatar(Request $request): void
    {
        $user = $this->currentUser();
        if (!$user) {
            $this->json(['error' => 'Usuário não encontrado'], 401);
            return;
        }
        if ($user->avatar()->delete()) {
            $token = Auth::generateToken($user);
            $this->json([
            'message'     => 'Avatar removido com sucesso',
            'token'       => $token,
            'avatar_file' => $user->getAvatarPath()
            ]);
        } else {
            $this->json(['error' => 'Erro ao remover o avatar'], 500);
        }
    }

    public function updateAvatar(): void
    {
        $user = $this->currentUser();
        if (!$user) {
            $this->json(['error' => 'Usuário não encontrado'], 401);
            return;
        }

        if (!isset($_FILES['avatar_file'])) {
            if ($this->requestExceededPostMaxSize()) {
                $this->json(['error' => 'Arquivo excede o tamanho máximo permitido pelo servidor'], 413);
                return;
            }

            $this->json(['error' => 'Arquivo inválido'], 400);
            return;
        }

        if ($_FILES['avatar_file']['error'] !== UPLOAD_ERR_OK) {
            $this->respondToAvatarUploadError($_FILES['avatar_file']['error']);
            return;
        }

        $image = $_FILES['avatar_file'];
        $avatar = $user->avatar();

        try {
            $updated = $avatar->update($image);
        } catch (\Throwable) {
            $this->json(['error' => 'Erro interno ao salvar o ícone'], 500);
            return;
        }

        if ($updated) {
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
        } elseif ($avatar->hasInternalFailure()) {
            $this->json(['error' => 'Erro interno ao salvar o ícone'], 500);
        } else {
            $this->json([
                'message' => 'Erro ao atualizar o ícone',
                'errors' => $user->errors()
            ], 422);
        }
    }

    private function respondToAvatarUploadError(int $errorCode): void
    {
        if (in_array($errorCode, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            $this->json(['error' => 'Arquivo excede o tamanho máximo permitido pelo servidor'], 413);
            return;
        }

        if ($errorCode === UPLOAD_ERR_NO_FILE) {
            $this->json(['error' => 'Arquivo inválido'], 400);
            return;
        }

        $this->json(['error' => 'Falha no envio do arquivo'], 422);
    }

    private function requestExceededPostMaxSize(): bool
    {
        $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        $postMaxSize = $this->iniSizeToBytes((string) ini_get('post_max_size'));

        return $contentLength > 0 && $postMaxSize > 0 && $contentLength > $postMaxSize;
    }

    private function iniSizeToBytes(string $size): int
    {
        $size = trim($size);
        if ($size === '') {
            return 0;
        }

        $unit = strtolower(substr($size, -1));
        $value = (int) $size;

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
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

        if ($user->banner()->update($image)) {
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
