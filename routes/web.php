<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\FileController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('manageapi/systemset')->group(function () {
    Route::get('/get', [SystemSettingController::class, 'getSystemSet']);
    Route::post('/edit', [SystemSettingController::class, 'editSystemSet']);
});

Route::prefix('manageapi/fileupload')->group(function () {
    Route::post('/upload', [FileController::class, 'upload']);
});
