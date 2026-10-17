<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

Route::get('/', function (): JsonResponse {
    return response()->json([
        'name' => config('app.name'),
        'version' => 'v1',
        'status' => 'ok',
        'documentation' => url('/docs/api-docs.json'),
    ]);
});

Route::get('/docs/api-docs.json', function () {
    $path = storage_path('api-docs/api-docs.json');

    abort_unless(is_file($path), 404);

    return response(File::get($path), 200, ['Content-Type' => 'application/json']);
})->name('openapi.json');
