<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PostImagesController;
use App\Http\Controllers\ProfileController;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/profile', [AuthController::class, 'profile'])->middleware('auth:api');
Route::get('/login/google', [GoogleController::class, 'redirectToGoogle'])->name('api.login.google');
Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback'])->name('api.google.callback');
// Password reset routes
Route::post('/password/email', [AuthController::class, 'sendPasswordResetLink']);
Route::post('/password/reset', [AuthController::class, 'resetPassword']);
Route::post('/check-username', [AuthController::class, 'checkUserName']);
Route::post('/check-email', [AuthController::class, 'checkEmail']);

// Email verification routes
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->name('verification.verify');
Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])
    ->middleware('auth:api');

Route::middleware(['auth:api'])->group(function () {
    Route::post('/update-profile', [ProfileController::class, 'updateProfile']);
    Route::resource('categories', CategoryController::class);
    Route::resource('posts', PostController::class);
    Route::post('/images', [PostImagesController::class, 'saveImage']);
});
