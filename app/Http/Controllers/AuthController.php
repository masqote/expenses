<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function register(RegisterRequest $request)
    {
        $user  = $this->authService->register($request->only(['name', 'email', 'password']));
        $token = $this->authService->issueToken($user);

        return response()->json(['token' => $token], 201);
    }

    public function login(LoginRequest $request)
    {
        $token = $this->authService->attemptLogin(
            $request->input('email'),
            $request->input('password')
        );

        if (! $token) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        return response()->json(['token' => $token], 200);
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request->user());

        return response()->noContent();
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->only(['id', 'name', 'email']));
    }
}
