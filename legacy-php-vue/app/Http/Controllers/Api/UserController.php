<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        $users = $query->paginate(15);

        return UserResource::collection($users)->response();
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create($data);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'created_user',
            'description' => "Created user {$user->email}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }

    public function show(User $user): UserResource
    {
        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $data = $request->validated();

        $user->update($data);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'updated_user',
            'description' => "Updated user {$user->email}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return new UserResource($user);
    }

    public function destroy(Request $request, User $user): Response
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'Cannot delete your own account.'], 422);
        }

        // F5: prevent lockout — block deletion of the last active administrator.
        if ($user->role === UserRole::Administrator || $user->role === UserRole::Administrator->value) {
            $otherActiveAdmins = User::query()
                ->where('id', '!=', $user->id)
                ->where(function ($q) {
                    $q->where('role', UserRole::Administrator->value)
                      ->orWhere('role', UserRole::Administrator);
                })
                ->where(function ($q) {
                    $q->whereNull('employment_status')
                      ->orWhere('employment_status', 'active');
                })
                ->count();

            if ($otherActiveAdmins < 1) {
                return response()->json([
                    'message' => 'Cannot delete the last active administrator. Promote another user first.',
                ], 422);
            }
        }

        $user->delete();

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'deleted_user',
            'description' => "Deleted user {$user->email}",
            'module' => 'auth',
            'resource_type' => User::class,
            'resource_id' => $user->id,
            'event' => 'deleted',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->noContent();
    }
}
