<?php

namespace App\Middleware;

use Core\Http\Request;
use Lib\Authentication\Auth;

class AdminAuthenticate extends Authenticate
{
    public function handle(Request $request): void
    {
        $uri = $_SERVER['REQUEST_URI'] ?? $request->getUri();
        if (preg_match('/auth\/login/i', $uri)) {
            return;
        }

        parent::handle($request);

        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        preg_match('/Bearer\s(\S+)/', $authHeader, $matches);
        $token = $matches[1] ?? null;

        $user = Auth::user($token);

        if (!$user || $user->role != 'Admin') {
            $this->forbidden();
        }
    }
}
