<?php

use App\Http\Controllers\Admin\ThreeDFileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/3d-files/{id}', [ThreeDFileController::class, 'show'])->name('3d-files.show');
    Route::get('/3d-files/{id}/file', [ThreeDFileController::class, 'file'])->name('3d-files.file');
    Route::get('/3d-files/{id}/download', [ThreeDFileController::class, 'download'])->name('3d-files.download');
    Route::post('/3d-files/upload', [ThreeDFileController::class, 'store'])->name('3d-files.upload');
});
