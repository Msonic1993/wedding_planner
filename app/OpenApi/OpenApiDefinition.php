<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Wedding Planet API",
 *     version="v1",
 *     description="Versioned REST API for Wedding Planet."
 * )
 *
 * @OA\Server(url="/", description="Current application server")
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Bearer"
 * )
 */
final class OpenApiDefinition {}
