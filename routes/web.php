<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\RevisorController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'homepage'])->name('homepage');
Route::get('/search/article', [PublicController::class, 'searchArticles'])->name('article.search');
Route::post('/lingua/{lang}', [PublicController::class, 'setLanguage'])->name('setLocale');
Route::get('/create', [ArticleController::class, 'create'])->name('article.create');
Route::get('/index', [ArticleController::class, 'index'])->name('article.index');
Route::get('/show/{article}', [ArticleController::class, 'show'])->name('article.show');
Route::get('/category/{category}', [ArticleController::class, 'byCategory'])->name('article.byCategory');

Route::middleware('isRevisor')->group(function () {
    Route::get('/revisor/index', [RevisorController::class, 'index'])->name('revisor.index');
    Route::patch('/accept/{article}', [RevisorController::class, 'accept'])->name('revisor.accept');
    Route::patch('/reject/{article}', [RevisorController::class, 'reject'])->name('revisor.reject');
    Route::patch('/revisor/undo', [RevisorController::class, 'undoLast'])->name('revisor.undo');
});

Route::middleware('auth')->group(function () {
    Route::get('/lavora-con-noi', [RevisorController::class, 'becomeRevisor'])->name('become.revisor');
    Route::post('/lavora-con-noi', [RevisorController::class, 'becomeRevisorSubmit'])->name('become.revisor.submit');
});

Route::get('/make/{user}/revisor', [RevisorController::class, 'makeRevisor'])->name('make.revisor');
