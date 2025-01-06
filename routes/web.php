<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ElasticController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('elastic')->group(function(){
    //Route::get('/addNews',[ElasticController::class, 'addNews']);
    //Route::get('/addInstagram',[ElasticController::class, 'addInstagram']);
    //Route::get('/addTwitter',[ElasticController::class, 'addTwitter']);
    Route::get('/search',[ElasticController::class, 'search']);
});