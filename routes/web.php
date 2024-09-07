<?php

use App\Http\Controllers\MailController;
use App\Http\Controllers\ShortenController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/mail', MailController::class)->name('mail.index');
Route::get('/shorten', ShortenController::class)->name('shorten.index');
