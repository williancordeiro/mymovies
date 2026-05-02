<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;

class AdminController extends Controller
{
    public function index(): void
    {
        $title = 'Admin Page';
        $this->render('admin/index', compact('title'));
    }
}
