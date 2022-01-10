<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\UserController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::apiResource('post', PostController::class);
Route::get('post/{post}/comments', [PostController::class, 'comments'])->name('post.comments');
Route::post('post/{post}/like', [PostController::class, 'like'])->name('post.like');
Route::post('post/{post}/unlike', [PostController::class, 'unlike'])->name('post.unlike');

Route::apiResource('category', CategoryController::class)->except(['show']);
Route::get('category/{category}/posts', [CategoryController::class, 'posts'])->name('category.posts');

Route::apiResource('tag', TagController::class);
Route::get('tag/{tag}/posts', [TagController::class, 'posts'])->name('tag.posts');

Route::apiResource('comment', CommentController::class)->except(['show']);

Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.forgot');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');

Route::prefix('user')->name('user.')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::get('/adminsList', [UserController::class, 'adminsList'])->name('adminsList');
    Route::get('/{user}/posts', [UserController::class, 'posts'])->name('posts');
    Route::get('/{user}/comments', [UserController::class, 'comments'])->name('comments');
    Route::get('/{user}/liked-posts', [UserController::class, 'likedPosts'])->name('likedPosts');
    Route::post('/{user}/promote-to-Admin', [UserController::class, 'promoteToAdmin'])->name('promoteToAdmin');
    Route::post('/{user}/demote-to-User', [UserController::class, 'demoteToUser'])->name('demoteToUser');
});

Route::prefix('profile')->name('profile.')->group(function () {
    Route::post('/update', [ProfileController::class, 'update'])->name('update');
    Route::post('/change-password', [ProfileController::class, 'changePassword'])->name('changePassword');
});
Route::prefix('upload')->name('upload.')->group(function () {
    Route::post('/upload-post-image', [UploadController::class, 'uploadPostImage'])->name('postImage');
    Route::post('/upload-profile-image', [UploadController::class, 'uploadProfileImage'])->name('profileImage');
});