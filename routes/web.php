<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadController;

// Main routes with kebab-case URLs and dot.notation names
Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/upload', [UploadController::class, 'index'])->name('upload.index');
Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');

Route::get('/gallery', function () {
    return view('gallery');
})->name('gallery.index');

Route::get('/batch/{id}', function ($id) {
    return view('batch.show', ['id' => $id]);
})->name('batch.show');

Route::get('/batch/{id}/gallery', function ($id) {
    return view('batch.gallery', ['id' => $id]);
})->name('batch.gallery');

Route::get('/batch/{id}/download', function ($id) {
    return view('batch.download', ['id' => $id]);
})->name('batch.download');

// Documentation routes
Route::get('/docs/task-and-purpose/{view}', function ($view) {
    return view("task-and-purpose.{$view}");
})->name('docs.task-and-purpose');
