<?php

use App\Controllers\HomeController;
use App\Controllers\UserController;
use App\Controllers\AdminController;
use Core\Router\Route;

Route::get('/', [HomeController::class, 'index'])->name('root');
Route::get('/flash', [HomeController::class, 'flash']);
Route::post('/auth/login', [UserController::class, 'login']);

Route::middleware('admin')->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
});
