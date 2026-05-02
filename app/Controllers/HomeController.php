<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        $title = 'Home Page';
        $this->render('admin/index', compact('title'));
    }

    public function flash(): void
    {
        $this->json([]);
    }
}
