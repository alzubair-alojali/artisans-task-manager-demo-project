<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

/**
 * @group Authentication
 *
 * APIs for managing user authentication.
 */
class AuthController extends Controller
{
    /**
     * Register a new user.
     *
     * Creates a new user record and returns an access token.
     *
     * @unauthenticated
     * @response 201 {
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "role": "user",
     *     "created_at": "2025-12-16 12:00:00"
     *   },
     *   "token": "1|sanctum_token...",
     *   "message": "User registered successfully"
     * }
     * @response 422 {
     *   "message": "The email has already been taken.",
     *   "errors": {
     *     "email": ["The email has already been taken."]
     *   }
     * }
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return (new UserResource($user))
            ->additional([
                'token' => $token,
                'message' => 'User registered successfully',
            ])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Log in a user.
     *
     * Authenticates a user and returns an access token.
     *
     * @unauthenticated
     * @response {
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "role": "user",
     *     "created_at": "2025-12-16 12:00:00"
     *   },
     *   "token": "1|sanctum_token...",
     *   "message": "Login successful"
     * }
     * @response 401 {
     *   "message": "Invalid login credentials"
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "email": ["The email field is required."]
     *   }
     * }
     */
    public function login(LoginRequest $request): UserResource|JsonResponse
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login credentials'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return (new UserResource($user))
            ->additional([
                'token' => $token,
                'message' => 'Login successful',
            ]);
    }

    /**
     * Log out the current user.
     *
     * Revokes the user's current access token.
     *
     * @response 204 {}
     */
    public function logout(Request $request): \Illuminate\Http\Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    /**
     * Redirect to Google for authentication.
     *
     * Returns the Google OAuth redirect URL.
     *
     * @unauthenticated
     * @response {
     *   "url": "https://accounts.google.com/o/oauth2/auth?..."
     * }
     */
    public function redirectToGoogle(): JsonResponse
    {
        return response()->json([
            'url' => Socialite::driver('google')->stateless()->redirect()->getTargetUrl(),
        ]);
    }

    /**
     * Handle Google authentication callback.
     *
     * Exchanges the Google code for a user and access token.
     *
     * @unauthenticated
     * @response {
     *   "token": "1|sanctum_token...",
     *   "user": { ... },
     *   "message": "Login successful"
     * }
     * @response 400 {
     *   "message": "Google login failed",
     *   "error": "Error details..."
     * }
     */
    public function handleGoogleCallback(): JsonResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::where('google_id', $googleUser->getId())->first();

            if (!$user) {
                $user = User::where('email', $googleUser->getEmail())->first();

                if ($user) {
                    $user->update(['google_id' => $googleUser->getId()]);
                } else {
                    $user = User::create([
                        'name' => $googleUser->getName(),
                        'email' => $googleUser->getEmail(),
                        'google_id' => $googleUser->getId(),
                        'password' => Hash::make(Str::random(24)),
                        'role' => UserRole::USER,
                    ]);
                }
            }

            $token = $user->createToken('google_auth_token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => new UserResource($user),
                'message' => 'Login successful',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Google login failed',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
