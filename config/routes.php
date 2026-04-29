<?php

use App\Controllers\HomeController;
use App\Controllers\UserController;
use Core\Router\Route;

// Authentication
Route::get('/', [HomeController::class, 'index'])->name('root');
Route::post('/auth/login', [UserController::class, 'login']);