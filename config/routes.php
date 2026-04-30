<?php

use App\Controllers\HomeController;
use App\Controllers\UserController;
use App\Controllers\AdminController;
use Core\Router\Route;

// Authentication
Route::get('/', [HomeController::class, 'index'])->name('root');
Route::post('/auth/login', [UserController::class, 'login']);

Route::get('/admin', [AdminController::class, 'index']);