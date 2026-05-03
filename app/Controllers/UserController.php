<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;
use App\Models\User;
use Lib\Authentication\Auth;
use Core\Http\Request;

class UserController extends Controller
{
    public function login(Request $request): void
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $email = $data['email'] ?? $request->getParam('email');
        $password = $data['password'] ?? $request->getParam('password');

        if (!$email || !$password) {
            \Lib\FlashMessage::danger('E-mail e senha são obrigatórios!');
            $this->json(['error' => 'E-mail e senha são obrigatórios'], 400);
            return;
        }

        $user = User::findByEmail($email);

        if ($user && $user->authenticate($password)) {
            $token = Auth::generateToken($user);

            \Lib\FlashMessage::success('O login foi um sucesso');
            $this->json([
                'token' => $token,
                'user' => [
                    'id'    => $user->id,
                    'username'  => $user->username,
                    'email' => $user->email,
                    'admin' => $user->admin
                ]
            ]);
            return;
        }

        \Lib\FlashMessage::danger('Credenciais inválidas!');
        $this->json(['error' => 'E-mail ou senha inválidos'], 401);
    }

    public function logout(Request $request): void
    {
            $this->json(['message' => 'Logout realizado com sucesso'], 200);
    }
}
