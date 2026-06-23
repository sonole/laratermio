<?php

use App\Http\Controllers\CvController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::view('/docs', 'docs')->name('docs');

Route::get('/cv', [CvController::class, 'show'])->name('cv');
