<?php

namespace App\Application\Auth\Data;

use App\Models\User;

readonly class AuthResult
{
    public function __construct(
        public User $user,
        public string $token,
    ) {}
}
