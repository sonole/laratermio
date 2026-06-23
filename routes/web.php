<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\CvController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/cv', [CvController::class, 'show'])->name('cv');
Route::view('/docs', 'docs')->name('docs');

Route::get('/robots.txt', RobotsController::class);
Route::get('/sitemap.xml', SitemapController::class);
