<?php

namespace App\Middleware;

use Core\Http\Middleware\Middleware;
use Core\Http\Request;
use Lib\Authentication\Auth;

class Authenticate implements Middleware
{
    public function handle(Request $request): void
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = null;

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        }

        if (!$token || !Auth::validateToken($token)) {
            $this->unauthorized();
        }
    }

    protected function unauthorized(): void
    {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['error' => 'Token inválido ou ausente']);
        exit;
    }

    protected function forbidden(): void
    {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['error' => 'Acesso negado']);
        exit;
    }
}
