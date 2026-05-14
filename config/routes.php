<?php

use App\Controllers\UsersController;
use App\Controllers\AdminController;
use Core\Router\Route;

Route::post('/auth/login', [UsersController::class, 'login']);
Route::post('/auth/register', [UsersController::class, 'register']);

Route::get('/flash', [FlashController::class, 'flash']);
Route::middleware('admin')->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
});

Route::middleware('auth')->group(function () {
    Route::post('/auth/logout', [UsersController::class, 'logout']);

    Route::put('/change/update', [UsersController::class, 'update']);
    Route::put('/change/email', [UsersController::class, 'changeEmail']);
    Route::post('/change/avatar', [UsersController::class, 'changeAvatar']);
});