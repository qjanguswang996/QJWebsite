<?php

use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\ThreeDFileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::redirect('/admin', '/admin/3d-files');

// Admin Guest Routes
Route::prefix('admin')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('admin.login.submit');
});

// Admin Protected Routes
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/3d-files', [ThreeDFileController::class, 'index'])->name('3d-files.index');
    Route::get('/3d-files/{id}', [ThreeDFileController::class, 'show'])->name('3d-files.show');
    Route::get('/3d-files/{id}/file', [ThreeDFileController::class, 'file'])->name('3d-files.file');
    Route::get('/3d-files/{id}/download', [ThreeDFileController::class, 'download'])->name('3d-files.download');
    Route::post('/3d-files/upload', [ThreeDFileController::class, 'store'])->name('3d-files.upload');
    Route::delete('/3d-files/{id}', [ThreeDFileController::class, 'destroy'])->name('3d-files.destroy');
});
