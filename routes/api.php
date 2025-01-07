<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ElasticsearchController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/add-user',[ElasticsearchController::class, 'addUser']);
Route::post('/set-user-notifier',[ElasticsearchController::class, 'addUserNotifier']);
Route::get('/users',[ElasticsearchController::class, 'getUsers']);

Route::get('/search', [ElasticsearchController::class, 'search']);
Route::get('/search-data', [ElasticsearchController::class, 'searchData']);
Route::get('/index', [ElasticsearchController::class, 'indexDocument']);
Route::post('/in', [ElasticsearchController::class, 'in']);
Route::post('/add-news', [ElasticsearchController::class, 'addNews']);
Route::post('/add-instagram', [ElasticsearchController::class, 'addInstagram']);
Route::post('/add-twitter', [ElasticsearchController::class, 'addTwitter']);