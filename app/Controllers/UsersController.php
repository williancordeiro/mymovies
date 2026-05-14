<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;
use App\Models\User;
use Lib\Authentication\Auth;
use Core\Http\Request;
use Lib\FlashMessage;
use Core\Constants\Constants;


class UsersController extends Controller {

    public function login(Request $request): void {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $email = $data['email'] ?? $request->getParam('email');
        $password = $data['password'] ?? $request->getParam('password');
        $username = $data['username'] ?? $request->getParam('username');
        //$role = $data['role'] ?? $request->getParam('role');
        $login = $email ?? $username;

        if (!$login || !$password) {
            FlashMessage::danger('E-mail e senha são obrigatórios!');
            $this->json(['error' => 'E-mail e senha são obrigatórios'], 400);
            return;
        }

        $user = User::findByEmail($login) ?? User::findByUsername($login);

        if ($user && $user->authenticate($password)) {
            $token = Auth::generateToken($user);

            FlashMessage::success('O login foi um sucesso');
            $this->json([
                'token' => $token,
                'user' => [
                    'id'    => $user->id,
                    'username'  => $user->username,
                    'handle' => $user->handle,
                    'email' => $user->email,
                    'role' => $user->role,
                    'avatar_file' => $user->avatarPath()
                ]
            ]);
            return;
        }

        FlashMessage::danger('Credenciais inválidas!');
        $this->json(['error' => 'E-mail ou senha inválidos'], 401);
    }

    public function register(Request $request): void {
        $json = file_get_contents('php://input');
        $decode = json_decode($json, true);

        $email = $decode['email'] ?? $request->getParam('email');
        $username = $decode['username'] ?? $request->getParam('username');
        $password = $decode['password'] ?? $request->getParam('password');
        $cleanUsername = preg_replace('/[^A-Za-z0-9]/', '', $username);
        $handle = strtolower($cleanUsername) . mt_rand(1000, 9999);
        $role = 'Default';
        $avatar_file = 'avatar.png';
        

        $data = [
            'email' => $email,
            'username' => $username,
            'password' => $password,
            'role' => $role,
            'handle' => $handle,
            'avatar_file' => $avatar_file
        ];

        if (!$data['email'] || !$data['username'] || !$data['password']) {
            FlashMessage::danger('Todos os campos são obrigatórios!');
            $this->json(['error' => 'Todos os campos são obrigatórios'], 400);
            return;
        }

        if (User::findByEmail($data['email'])) {
            $token = Auth::generateToken($user);
            FlashMessage::danger('E-mail já cadastrado');
            $this->json(['error' => 'E-mail já cadastrado'], 400);
            return;
        }

        $user = new User($data);

        if ($user->save()) {
            $token = Auth::generateToken($user);
            FlashMessage::success('O cadastro foi um sucesso');
            $this->json([
                'token' => $token,
                'user' => [
                    'id'    => $user->id,
                    'username'  => $user->username,
                    'handle' => $user->handle,
                    'email' => $user->email,
                    'role' => $user->role,
                    'avatar_file' => $user->avatarPath()
                ]
            ], 201);
        } else {
            $this->json(['error' => $user->errors()], 400);
        }

    }

    public function update(Request $request): void {
        $user = $this->currentUser();

        if (!$user) {
            $this->json(['error' => 'Usuário não encontrado'], 401);
            return;
        }

        $json = file_get_contents('php://input');
        $decode = json_decode($json, true);

        $username = $decode['username'] ?? $request->getParam('username');
        $handle = $decode['handle'] ?? $request->getParam('handle');

        $data = [
            'username' => $username,
            'handle' => $handle,
        ];

        if (!$data['username'] || !$data['handle']) {
            FlashMessage::danger('Nome e indentificador são obrigatórios');
            $this->json(['error' => 'Todos os campos são obrigatórios'], 400);
            return;
        }

        if ($user->update($data)) {
            $token = Auth::generateToken($user);
            FlashMessage::success('Seus dados foram atualizados!');
            $this->json([
                'token' => $token,
                'user' => [
                    'id'    => $user->id,
                    'username'  => $user->username,
                    'handle' => $user->handle,
                    'email' => $user->email,
                    'role' => $user->role,
                    'avatar_file' => $user->avatarPath()
                ]
            ]);
        } else {
            $this->json(['error' => $user->errors()], 500);
        }
    }

    public function changeEmail(Request $request): void {
        $user = $this->currentUser();

        if (!$user) {
            $this->json(['error' => 'Usuário não encontrado'], 401);
            return;
        }

        $json = file_get_contents('php://input');
        $decode = json_decode($json, true);

        $email = $decode['email'] ?? $request->getParam('email');
        $password = $decode['password'] ?? $request->getParam('password');

        $data = [
            'email' => $email,
            'password' => $password
        ];

        if (!$data['email'] || !$data['password']) {
            $token = Auth::generateToken($user);
            FlashMessage::danger('O novo e-mail e a senha são obrigatórios');
            $this->json(['error' => 'Todos os campos são obrigatórios'], 400);
            return;
        }

        if (!$user->authenticate($password)) {
            FlashMessage::danger('A senha está incorreta');
            $this->json(['error' => 'A senha está incorreta'], 400);
            return;
        }

        if (User::findByEmail($data['email'])) {
            $token = Auth::generateToken($user);
            FlashMessage::danger('E-mail já cadastrado');
            $this->json(['error' => 'E-mail já cadastrado'], 400);
            return;
        }

        if ($user->update($data)) {
            $token = Auth::generateToken($user);
            FlashMessage::success('Seu e-mail foi atualizado!');
            $this->json([
                'token' => $token,
                'user' => [
                    'id'    => $user->id,
                    'username'  => $user->username,
                    'handle' => $user->handle,
                    'email' => $user->email,
                    'role' => $user->role,
                    'avatar_file' => $user->avatarPath()
                ]
            ]);
        } else {
            $this->json(['error' => $user->errors()], 500);
        }
    }

    public function changeAvatar(Request $request): void {
        $user = $this->currentUser();

        if (!$user) {
            $this->json(['error' => 'Usuário não encontrado'], 401);
            return;
        }

        if (!isset($_FILES['avatar_file']) || $_FILES['avatar_file']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'Arquivo inválido'], 400);
            return;
        }

        $file = $_FILES['avatar_file'];

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png'];

        if (!in_array($extension, $allowedExtensions)) {
            FlashMessage::danger('Somente arquivos JPG, JPEG e PNG são permitidos');
            $this->json(['error' => 'Somente arquivos JPG, JPEG e PNG são permitidos'], 400);
            return;
        }

        $newFileName = $user->id . '_avatar.' . $extension;
        $uploadDir = (string) Constants::rootPath()->join('public/assets/uploads/');
        $destination = $uploadDir . $newFileName;

        if ($user->avatar_file && file_exists($uploadDir . $user->avatar_file)) {
            unlink($uploadDir . $user->avatar_file);
        }

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $user->update(['avatar_file' => $newFileName]);
            $token = Auth::generateToken($user);
            FlashMessage::success('Icone atualizado com sucesso');
            
            $this->json([
                'message' => 'Icone atualizado com sucesso',
                'token' => $token,
                'user' => [
                    'id'    => $user->id,
                    'username'  => $user->username,
                    'handle' => $user->handle,
                    'email' => $user->email,
                    'role' => $user->role,
                    'avatar_file' => $user->avatarPath()
                ]
            ]);
        } else {
            $this->json(['error' => 'Erro ao mover o arquivo físico'], 500);
        }

    }

    public function logout(Request $request): void
    {
            $this->json(['message' => 'Logout realizado com sucesso'], 200);
    }
}
