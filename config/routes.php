<?php

use App\Controllers\UsersController;
use App\Controllers\AdminController;
use App\Controllers\HomeController;
use App\Controllers\ProfileController;
use App\Controllers\MoviesController;
use Core\Router\Route;
use App\Controllers\GalleryController;
use App\Controllers\FavoritesController;
use App\Controllers\PlaylistController;

Route::post('/auth/login', [UsersController::class, 'login']);
Route::post('/auth/register', [UsersController::class, 'create']);
Route::get('/movies', [HomeController::class, 'index']);
Route::get('/movies/search', [MoviesController::class, 'search']);

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
    Route::put('/change/password', [UsersController::class, 'changePassword']);
    Route::post('/change/avatar', [ProfileController::class, 'updateAvatar']);
    Route::post('/change/banner', [ProfileController::class, 'updateBanner']);
    Route::delete('/change/avatar', [ProfileController::class, 'deleteAvatar']);

    Route::post('/gallery/images', [GalleryController::class, 'addImage']);
    Route::get('/gallery/images', [GalleryController::class, 'listImages']);
    Route::delete('/gallery/images/{id}', [GalleryController::class, 'deleteImage']);
});

Route::middleware('editor')->group(function () {
    Route::get('/editor', [EditorController::class, 'index']);
});

Route::get('/movies/{id}', [HomeController::class, 'show']);

Route::middleware('auth')->group(function () {
    Route::post('/movies/rate', [MoviesController::class, 'rate']);
    Route::delete('/movies/rate/{movie_id}', [MoviesController::class, 'unrate']);
    Route::post('/movies/favorites', [FavoritesController::class, 'create']);
    Route::delete('/movies/favorites/{movie_id}', [FavoritesController::class, 'delete']);
    Route::get('/favorites', [FavoritesController::class, 'index']);
    Route::post('/playlists', [PlaylistController::class, 'create']);
    Route::get('/playlists', [PlaylistController::class, 'index']);
    Route::get('/playlists/{id}', [PlaylistController::class, 'show']);
    Route::put('/playlists/{id}', [PlaylistController::class, 'update']);
    Route::delete('/playlists/{id}', [PlaylistController::class, 'delete']);
});


Route::get('/users/{handle}/ratings', [UsersController::class, 'ratings']);
