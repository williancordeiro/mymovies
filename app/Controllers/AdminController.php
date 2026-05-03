<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;

class AdminController extends Controller
{
    public function index(): void
    {
        $this->json(['message' => 'Admin area']);
    }
}
