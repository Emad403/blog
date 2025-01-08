<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ElasticsearchController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/add-fake',[ElasticsearchController::class, 'addFakeData']);


Route::post('/add-user',[ElasticsearchController::class, 'addUser']);
Route::post('/set-user-notifier',[ElasticsearchController::class, 'addUserNotifier']);
Route::get('/users',[ElasticsearchController::class, 'getUsers']);
Route::get('/user',[ElasticsearchController::class, 'getUser']);

Route::post('/add-news', [ElasticsearchController::class, 'addNews']);
Route::post('/add-instagram', [ElasticsearchController::class, 'addInstagram']);
Route::post('/add-twitter', [ElasticsearchController::class, 'addTwitter']);
Route::post('/add-post', [ElasticsearchController::class, 'addPost']);

Route::get('/search-data', [ElasticsearchController::class, 'searchData']);
