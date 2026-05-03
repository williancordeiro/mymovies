<?php

namespace App\Middleware;

use Core\Http\Request;
use Lib\Authentication\Auth;

class AdminAuthenticate extends Authenticate
{
    public function handle(Request $request): void
    {
        parent::handle($request);

        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        preg_match('/Bearer\s(\S+)/', $authHeader, $matches);
        $token = $matches[1] ?? null;

        $user = Auth::user($token);

        if (!$user || $user->admin != 1) {
            $this->forbidden();
        }
    }
}
