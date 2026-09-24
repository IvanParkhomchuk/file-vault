<?php

use App\Http\Controllers\FileDeletionController;
use App\Http\Controllers\FileManagementController;
use App\Http\Controllers\FileUploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FileManagementController::class, 'index'])->name('files.index');
Route::get('/files', [FileManagementController::class, 'list'])->name('files.list');

Route::post('/files', FileUploadController::class)->name('files.store');
Route::delete('/files/{file}', FileDeletionController::class)->name('files.destroy');
