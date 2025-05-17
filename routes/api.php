<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FacebookController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PostImagesController;
use App\Http\Controllers\PostReactionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ThreadController;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
Route::get('/login/facebook', [FacebookController::class, 'redirectToFacebook'])->name('api.login.facebook');
Route::get('/auth/facebook/callback', [FacebookController::class, 'handleFacebookCallback'])->name('api.facebook.callback');

// Email verification routes
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->name('verification.verify');
Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])
    ->middleware('auth:api');

Route::middleware(['auth:api'])->group(function () {
    Route::post('/profile', [ProfileController::class, 'updateProfile']);
    Route::get('/user-profile',[ProfileController::class,'getProfile']);

    Route::resource('categories', CategoryController::class);
    Route::resource('threads', ThreadController::class);
    Route::post('/images', [PostImagesController::class, 'saveImage']);


    Route::get('/threads/{thread_id}/posts', [PostController::class, 'index']);
    Route::post('/threads/{thread_id}/posts', [PostController::class, 'store']);
    Route::put('/posts/{post_id}', [PostController::class, 'update']);
    Route::delete('/posts/{post_id}', [PostController::class, 'destroy']);
    Route::post('/posts/{post_id}/reactions', [PostReactionController::class, 'store']);
    Route::delete('/posts/{post_id}/reactions', [PostReactionController::class, 'destroy']);
});
