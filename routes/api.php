<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::get('/presence', [\App\Http\Controllers\Api\PresenceController::class, 'index']);
Route::get('/presence/get/{id}', [\App\Http\Controllers\Api\PresenceController::class, 'show']);
Route::post('/presence/add', [\App\Http\Controllers\Api\PresenceController::class, 'store']);
Route::post('/presence/update/{id}', [\App\Http\Controllers\Api\PresenceController::class, 'update']);
Route::get('/presence/delete/{id}', [\App\Http\Controllers\Api\PresenceController::class, 'destroy']);
Route::get('/presence/toggle/{id}', [\App\Http\Controllers\Api\PresenceController::class, 'toggleApproved']);

Route::get('/presence_trans', [\App\Http\Controllers\Api\PresenceTransController::class, 'index']);
Route::get('/presence_trans/get/{id}', [\App\Http\Controllers\Api\PresenceTransController::class, 'show']);
Route::post('/presence_trans/add', [\App\Http\Controllers\Api\PresenceTransController::class, 'store']);
Route::post('/presence_trans/update/{id}', [\App\Http\Controllers\Api\PresenceTransController::class, 'update']);
Route::get('/presence_trans/delete/{id}', [\App\Http\Controllers\Api\PresenceTransController::class, 'destroy']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
