<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function register(array $data): User
    {
        return $this->userRepository->create($data);
    }

    public function issueToken(User $user): string
    {
        return $user->createToken('auth-token')->plainTextToken;
    }

    public function attemptLogin(string $email, string $password): ?string
    {
        if (! Auth::attempt(['email' => $email, 'password' => $password])) {
            return null;
        }

        /** @var User $user */
        $user = Auth::user();

        return $this->issueToken($user);
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
