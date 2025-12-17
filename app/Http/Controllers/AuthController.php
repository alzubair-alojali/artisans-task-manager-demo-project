<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
}
