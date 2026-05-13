<?php

namespace Config;

class App
{
    public static array $middlewareAliases = [
        'auth' => \App\Middleware\Authenticate::class,
        'admin' => \App\Middleware\AdminAuthenticate::class,
        'editor' => \App\Middleware\EditorAuthenticate::class
    ];
}
