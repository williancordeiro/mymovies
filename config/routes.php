<?php

use App\Controllers\UserController;
use App\Controllers\AdminController;
use Core\Router\Route;

Route::post('/auth/login', [UserController::class, 'login']);

Route::get('/flash', [FlashController::class, 'flash']);
Route::middleware('admin')->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
});
