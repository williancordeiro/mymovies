<?php

use App\Controllers\UserController;
use App\Controllers\AdminController;
use App\Controllers\HomeController;
use Core\Router\Route;

Route::post('/auth/login', [UserController::class, 'login']);

Route::get('/flash', [FlashController::class, 'flash']);
Route::middleware('admin')->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
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
