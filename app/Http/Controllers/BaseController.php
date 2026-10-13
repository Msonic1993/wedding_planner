<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\MessageBag;

abstract class BaseController
{
    /** @param array<mixed>|object|null $data */
    protected function success(mixed $data = null, ?string $message = null, int $status = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
        ], $status);
    }

    /** @param array<string, mixed>|MessageBag|null $errors */
    public static function errorResponse(string $message, int $status, array|MessageBag|null $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors instanceof MessageBag ? $errors->toArray() : $errors,
        ], $status);
    }
}
