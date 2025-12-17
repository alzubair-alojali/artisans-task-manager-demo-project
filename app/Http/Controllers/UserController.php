<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @group User Management
 *
 * APIs for managing users.
 */
class UserController extends Controller
{
    /**
     * List all users.
     *
     * Retrieve a list of users with filtering and sorting.
     *
     * @summary List Users
     * @response \App\Http\Resources\UserResource[]
     */
    public function index(): AnonymousResourceCollection
    {
        $users = QueryBuilder::for(User::class)
            ->allowedFilters(['name', 'email', 'role'])
            ->allowedSorts(['name', 'created_at'])
            ->get();

        return UserResource::collection($users);
    }

    /**
     * Create a new user.
     *
     * Create a new user record and assign a role.
     *
     * @summary Create User
     * @response 201 \App\Http\Resources\UserResource
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        $user->assignRole($request->role);

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show a user.
     *
     * Retrieve details of a specific user.
     *
     * @summary Show User
     * @response \App\Http\Resources\UserResource
     */
    public function show(User $user): UserResource
    {
        return new UserResource($user);
    }

    /**
     * Update a user.
     *
     * Update user details and sync role.
     *
     * @summary Update User
     * @response \App\Http\Resources\UserResource
     */
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        $user->syncRoles([$request->role]);

        return new UserResource($user);
    }

    /**
     * Delete a user.
     *
     * Remove a user from the system.
     *
     * @summary Delete User
     * @response 204 {}
     */
    public function destroy(User $user): Response
    {
        $user->delete();

        return response()->noContent();
    }
}
