<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PodPatientController;
use App\Http\Controllers\DrugController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\Auth\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Auth routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('pod-patients')->group(function () {
    Route::get('/', [PodPatientController::class, 'index']);
    Route::post('/', [PodPatientController::class, 'store']);
    Route::get('/{podPatient}', [PodPatientController::class, 'show']);
    Route::put('/{podPatient}', [PodPatientController::class, 'update']);
    Route::patch('/{podPatient}', [PodPatientController::class, 'update']);
    Route::delete('/{podPatient}', [PodPatientController::class, 'destroy']);
});

Route::prefix('drugs')->group(function () {
    Route::get('/', [DrugController::class, 'index']);
    Route::post('/', [DrugController::class, 'store']);
    Route::get('/{drug}', [DrugController::class, 'show']);
    Route::put('/{drug}', [DrugController::class, 'update']);
    Route::patch('/{drug}', [DrugController::class, 'update']);
    Route::delete('/{drug}', [DrugController::class, 'destroy']);
});

Route::prefix('brands')->group(function () {
    Route::get('/', [BrandController::class, 'index']);
    Route::post('/', [BrandController::class, 'store']);
    Route::get('/{brand}', [BrandController::class, 'show']);
    Route::put('/{brand}', [BrandController::class, 'update']);
    Route::patch('/{brand}', [BrandController::class, 'update']);
    Route::delete('/{brand}', [BrandController::class, 'destroy']);
});
