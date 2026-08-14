<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/** @OA\Schema(schema="ErrorResponse",required={"success","message","data","errors"},@OA\Property(property="success",type="boolean",example=false),@OA\Property(property="message",type="string",example="Przesłane dane są nieprawidłowe."),@OA\Property(property="data",nullable=true),@OA\Property(property="errors",type="object",nullable=true,additionalProperties=@OA\Schema(type="array",@OA\Items(type="string")))) */
final class ErrorResponseSchema {}
