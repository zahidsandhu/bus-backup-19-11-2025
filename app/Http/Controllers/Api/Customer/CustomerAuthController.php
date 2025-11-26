<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Customer\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class CustomerAuthController extends Controller
{
    /**
     * Register a new customer account.
     */
    public function signup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful.',
            'data' => [
                'token' => $token,
                'user' => new UserResource($user),
            ],
        ], 201);
    }

    /**
     * Authenticate an existing customer and issue an API token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if ($user && $user->status === \App\Enums\UserStatusEnum::BANNED) {
            Auth::logout();

            return response()->json([
                'success' => false,
                'message' => 'Your account has been banned. Please contact an administrator to activate your account.',
            ], 403);
        }

        if ($user && $user->hasTwoFactorEnabled()) {
            Auth::logout();

            return response()->json([
                'success' => false,
                'message' => 'Two-factor authentication is enabled for this account. Please complete login via the web portal.',
                'code' => 'two_factor_required',
            ], 403);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'token' => $token,
                'user' => new UserResource($user),
            ],
        ]);
    }

    /**
     * Revoke the current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user && $request->user()?->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }
}
