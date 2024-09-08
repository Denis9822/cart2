<?php

use App\Http\Controllers\Api\WizardStatsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/migration', [WizardStatsController::class, 'migration'])->name('migration');
Route::get('/save', [WizardStatsController::class, 'save'])->name('save');
