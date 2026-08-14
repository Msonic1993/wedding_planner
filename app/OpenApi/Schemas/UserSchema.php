<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/** @OA\Schema(schema="User",required={"id","name","email","roles","created_at"},@OA\Property(property="id",type="integer",format="int64",example=1),@OA\Property(property="name",type="string",example="Anna Kowalska"),@OA\Property(property="email",type="string",format="email",example="anna@example.com"),@OA\Property(property="email_verified_at",type="string",format="date-time",nullable=true),@OA\Property(property="roles",type="array",@OA\Items(type="string",enum={"bride","groom","vendor","admin"})),@OA\Property(property="created_at",type="string",format="date-time")) */
final class UserSchema {}
