<?php

namespace App\Middleware;

use Core\Http\Request;
use Lib\Authentication\Auth;

class EditorAuthenticate extends Authenticate
{
    public function handle(Request $request): void
    {
        parent::handle($request);

        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        preg_match('/Bearer\s(\S+)/', $authHeader, $matches);
        $token = $matches[1] ?? null;

        $user = Auth::user($token);

        /** @phpstan-ignore-next-line */
        if (!$user || $user->editor != 1) {
            $this->forbidden();
        }
    }
}
