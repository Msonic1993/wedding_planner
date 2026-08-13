<?php

namespace App\Application\Auth\Services;

use App\Application\Auth\Contracts\AuthenticationService;
use App\Application\Auth\Data\AuthResult;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SanctumAuthenticationService implements AuthenticationService
{
    public function register(array $data): AuthResult
    {
        return DB::transaction(function () use ($data): AuthResult {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            Role::findOrCreate($data['role'], 'web');
            $user->assignRole($data['role']);

            return new AuthResult($user, $this->createToken($user, $data['device_name'] ?? 'api'));
        });
    }

    public function login(array $data): ?AuthResult
    {
        $user = User::query()->where('email', $data['email'])->first();

        if (! $user instanceof User || ! Hash::check($data['password'], $user->password)) {
            return null;
        }

        return new AuthResult($user, $this->createToken($user, $data['device_name'] ?? 'api'));
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    public function deleteAccount(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            $user->delete();
        });
    }

    private function createToken(User $user, string $deviceName): string
    {
        return $user->createToken($deviceName)->plainTextToken;
    }
}
