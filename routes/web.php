<?php

use Illuminate\Support\Facades\Route;
use  App\Http\Controllers\Auth\ProfileController;
use  App\Http\Controllers\Auth\PostController;
use  App\Http\Controllers\MessageController;
use  App\Events\MessageSent;
use  App\Http\Controllers\Auth\GoogleAuthController;


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
Route::get('/login', function () {
    return view('auth.signin');
});
Route::get('/test-upload', function () {
    return view('auth.test-upload');
});

Route::get('/test-image', function () {
    $img = Image::canvas(100, 100, '#ff0000');
    $img->save(storage_path('app/public/watermark.jpg'));
    return asset('storage/watermark.jpg');
});


Route::get("auth/google",[GoogleAuthController::class,"redirectToGoogle"])->name("redirect.google");
Route::get("auth/google/callback",[GoogleAuthController::class,"handleGoogleCallback"]);
Route::get('/', function () {
    return view('welcome');
});
Broadcast::routes(['middleware' => ['auth:api']]);
Route::get('/pusher', function () {
    return view('pusher');
});
Route::get('/pusher2', function () {
    return view('pusher2');
});

Route::get('/pusher3', function () {
    return view('pusher3');
});

Route::get('/user/post',[MessageController::class,'showForm']);
Route::post('/user/postSave',[MessageController::class,'save'])->name('post.save');

Route::get('/postuser',[PostController::class,'showForm']);
Route::get('/postuser', function () {
    return view('testuploads');
});

// Route::get('/userpost',[PostController::class,'showForm']);


// Route::resource('profiles',ProfileController::class)->names('profiles');
Route::resource('testpost',PostController::class)->names('testpost');

Route::get('/test-broadcast', function () {
    $message = (object)[
        'id' => 146,
        'sender_id' => 92,
        'receiver_id' => 91,
        'message' => 'receive',
        'created_at' => now(),
    ];

    // ✅ Broadcast the event
    event(new MessageSent($message));

    // ✅ Return the message data
    return response()->json([
        'success' => true,
        'broadcasted_data' => $message
    ]);
});