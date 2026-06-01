<?php

use App\Controllers\UsersController;
use App\Controllers\AdminController;
use App\Controllers\HomeController;
use App\Controllers\ProfileController;
use Core\Router\Route;

Route::post('/auth/login', [UsersController::class, 'login']);
Route::post('/auth/register', [UsersController::class, 'create']);
Route::get('/movies', [HomeController::class, 'index']);

Route::get('/flash', [FlashController::class, 'flash']);
Route::middleware('admin')->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
});

Route::middleware('auth')->group(function () {
    Route::post('/auth/logout', [UsersController::class, 'logout']);
    Route::delete('/account/delete', [UsersController::class, 'delete']);
    Route::get('/list/users', [UsersController::class, 'listAll']);

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile/update', [ProfileController::class, 'update']);
    Route::put('/change/email', [UsersController::class, 'changeEmail']);
    Route::post('/change/avatar', [ProfileController::class, 'updateAvatar']);
    Route::post('/change/banner', [ProfileController::class, 'updateBanner']);
    Route::delete('/change/avatar', [ProfileController::class, 'deleteAvatar']);
});

Route::middleware('editor')->group(function () {
    Route::get('/editor', [EditorController::class, 'index']);
});

Route::get('/movies/{id}', [HomeController::class, 'show']);

Route::middleware('auth')->group(function () {
    Route::post('/movies/rate', [HomeController::class, 'rate']);
});


Route::get('/users/{handle}/ratings', [UsersController::class, 'ratings']);
