<?php

namespace App\Auth;

use App\Services\JsonUserRepository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Auth\GenericUser;

class JsonUserProvider implements UserProvider
{
    public function __construct(private JsonUserRepository $users, private Hasher $hasher) {}

    public function retrieveById($identifier): ?Authenticatable
    {
        return $this->wrap($this->users->findById((string) $identifier));
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return null; // sem "lembrar de mim"
    }

    public function updateRememberToken(Authenticatable $user, $token): void {}

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        return isset($credentials['email']) ? $this->wrap($this->users->findByEmail((string) $credentials['email'])) : null;
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        return $this->hasher->check((string) ($credentials['password'] ?? ''), $user->getAuthPassword());
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void {}

    private function wrap(?array $user): ?Authenticatable
    {
        return $user ? new GenericUser($user + ['remember_token' => null]) : null;
    }
}
