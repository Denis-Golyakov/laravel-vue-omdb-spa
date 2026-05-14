<?php

use App\Http\Controllers\Api\MovieSearchController;
use App\Http\Controllers\Api\SearchHistoryController;
use Illuminate\Support\Facades\Route;

Route::get('movies/search', [MovieSearchController::class, 'search']);
Route::get('movies/{imdbId}', [MovieSearchController::class, 'show'])
    ->where('imdbId', 'tt\d+');
Route::get('searches', [SearchHistoryController::class, 'index']);
