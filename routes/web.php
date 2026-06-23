<?php

use App\Http\Controllers\CvController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::view('/docs', 'docs')->name('docs');

Route::get('/cv', [CvController::class, 'show'])->name('cv');

Route::get('/robots.txt', RobotsController::class);
Route::get('/sitemap.xml', SitemapController::class);
