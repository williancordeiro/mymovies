<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ProfileImages;
use Lib\Authentication\Auth;
use Core\Http\Request;
use Lib\FlashMessage;
use Core\Constants\Constants;

class UsersController extends Controller
{
    public function login(Request $request): void
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $email = $data['email'] ?? $request->getParam('email');
        $password = $data['password'] ?? $request->getParam('password');
        $username = $data['username'] ?? $request->getParam('username');

        if (!$email || !$password) {
            FlashMessage::danger('E-mail e senha são obrigatórios!');
            $this->json(['error' => 'E-mail e senha são obrigatórios'], 400);
            return;
        }

        $user = User::findByEmail($email);

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
                    'avatar_file' => $user->getAvatarPath(),
                    'banner_file' => $user->getBannerPath()
                ]
            ]);
            return;
        }

        FlashMessage::danger('Credenciais inválidas!');
        $this->json([
            'message' => 'Credenciais inválidas!',
            'errors' => 'Credenciais inválidas!'
        ], 401);
    }

    public function create(Request $request): void
    {
        $json = file_get_contents('php://input');
        $decode = json_decode($json, true);

        $email = $decode['email'] ?? $request->getParam('email');
        $username = $decode['username'] ?? $request->getParam('username');
        $password = $decode['password'] ?? $request->getParam('password');
        $cleanUsername = preg_replace('/[^A-Za-z0-9]/', '', $username);
        $handle = strtolower($cleanUsername) . mt_rand(1000, 9999);
        $role = 'Default';
        $avatar_file = 'avatar.png';
        $banner_file = 'banner.png';

        $data = [
            'email' => $email,
            'username' => $username,
            'password' => $password,
            'role' => $role,
            'handle' => $handle,
            'avatar_file' => $avatar_file,
            'banner_file' => $banner_file
        ];

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
                    'avatar_file' => $user->getAvatarPath(),
                    'banner_file' => $user->getBannerPath()
                ]
            ], 201);
        } else {
            $this->json([
                'message' => 'Erro na validação dos dados',
                'errors' => $user->errors()
                ], 422);
        }
    }

    public function listAll(Request $request): void
    {
        $page = (int) ($request->getParam('page') ?? 1);
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $users = User::all();
        $total = count($users);
        $users = array_slice($users, $offset, $perPage);

        $this->json([
        'users' => array_map(fn($user) => [
            'id'         => $user->id,
            'username'   => $user->username,
            'handle'     => $user->handle,
            'email'      => $user->email,
            'role'       => $user->role,
            'avatar_file' => $user->getAvatarPath()
        ], $users),
        'total' => $total,
        'page'  => $page,
        'pages' => ceil($total / $perPage)
        ]);
    }

    public function delete(Request $request): void
    {
        $user = $this->currentUser();

        if (!$user) {
            $this->json(['error' => 'Usuário não encontrado'], 401);
            return;
        }

        $json = file_get_contents('php://input');
        $decode = json_decode($json, true);
        $password = $decode['password'] ?? $request->getParam('password');

        if (!$password) {
            FlashMessage::danger('A senha é obrigatória!');
            $this->json(['error' => 'A senha é obrigatória!'], 400);
            return;
        }

        if (!$user->authenticate($password)) {
            FlashMessage::danger('A senha está incorreta!');
            $this->json(['error' => 'A senha está incorreta!'], 401);
            return;
        }

        $uploadDir = (string) Constants::rootPath()->join('public/assets/uploads/');

        if ($user->avatar_file && $user->avatar_file !== 'avatar.png') {
            $filePath = $uploadDir . $user->avatar_file;

            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        if ($user->destroy()) {
            FlashMessage::success('Sua conta foi deletada!');
            $this->json(['message' => 'Sua conta foi deletada!'], 200);
        } else {
            $this->json(['error' => 'Erro ao deletar a conta'], 500);
        }
    }

    public function changeEmail(Request $request): void
    {
        $user = $this->currentUser();

        if (!$user) {
            $this->json(['error' => 'Usuário não encontrado'], 401);
            return;
        }

        $json = file_get_contents('php://input');
        $decode = json_decode($json, true);

        $email = $decode['email'] ?? $request->getParam('email');
        $password = $decode['password'] ?? $request->getParam('password');

        if (!$user->authenticate($password)) {
            FlashMessage::danger('A senha está incorreta!');
            $this->json(['error' => 'A senha está incorreta!'], 401);
            return;
        }

        $data = [
            'email' => $email,
        ];

        $user->email = $data['email'];

        if ($user->isValid() && $user->update($data)) {
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
                    'avatar_file' => $user->getAvatarPath(),
                    'banner_file' => $user->getBannerPath()
                ]
            ]);
        } else {
            $this->json([
                'message' => 'Erro na validação dos dados',
                'errors' => $user->errors()
            ], 422);
        }
    }

    public function logout(Request $request): void
    {
            $this->json(['message' => 'Logout realizado com sucesso'], 200);
    }

    public function ratings(Request $request): void
    {
        $handle = $request->getParam('handle');
        $db = \Core\Database\Database::getDatabaseConn();

        $query = "SELECT r.movie_id, r.rating, u.username 
                  FROM movie_ratings r 
                  JOIN users u ON r.user_id = u.id 
                  WHERE u.handle = ?
                  ORDER BY r.created_at DESC";

        $stmt = $db->prepare($query);
        $stmt->execute([$handle]);
        $ratings = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->json(['ratings' => $ratings]);
    }
}
