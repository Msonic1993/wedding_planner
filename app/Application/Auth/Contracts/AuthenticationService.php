<?php

namespace App\Application\Auth\Contracts;

use App\Application\Auth\Data\AuthResult;
use App\Models\User;

interface AuthenticationService
{
    /** @param array{name: string, email: string, password: string, role: string, device_name?: string} $data */
    public function register(array $data): AuthResult;

    /** @param array{email: string, password: string, device_name?: string} $data */
    public function login(array $data): ?AuthResult;

    public function logout(User $user): void;

    public function deleteAccount(User $user): void;
}
