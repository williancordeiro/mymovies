<?php

use App\Controllers\UsersController;
use App\Controllers\AdminController;
use App\Controllers\HomeController;
use Core\Router\Route;

Route::post('/auth/login', [UsersController::class, 'login']);
Route::post('/auth/register', [UsersController::class, 'register']);

Route::get('/flash', [FlashController::class, 'flash']);
Route::middleware('admin')->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
});

Route::middleware('auth')->group(function () {
    Route::post('/auth/logout', [UsersController::class, 'logout']);

    Route::put('/profile/update', [UsersController::class, 'create']);
    Route::put('/change/email', [UsersController::class, 'changeEmail']);
    Route::post('/change/avatar', [UsersController::class, 'changeAvatar']);
});
Route::middleware('editor')->group(function () {
    Route::get('/editor', [EditorController::class, 'index']);
});
Route::middleware('auth')->group(function () {
    Route::post('/auth/logout', [UserController::class, 'logout']);
});
Route::middleware('auth')->group(function () {
    Route::get('/', [HomeController::class, 'index']);
});
