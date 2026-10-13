<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs/api-docs.json', function () {
    $path = storage_path('api-docs/api-docs.json');

    abort_unless(is_file($path), 404);

    return response(File::get($path), 200, ['Content-Type' => 'application/json']);
})->name('openapi.json');
