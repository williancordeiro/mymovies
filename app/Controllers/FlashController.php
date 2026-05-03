<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;

class FlashController extends Controller
{
    public function flash(): void
    {
            $this->json([]);
    }
}
