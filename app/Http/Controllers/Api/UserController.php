<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles.permissions');

        return response()->json([
            'ok' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'roles' => $user->roles->pluck('name')->values(),
                'permissions' => $user->permissionNames(),
            ],
        ]);
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => User::query()
                ->with('roles:id,name,label')
                ->orderBy('name')
                ->get()
                ->map(fn (User $user) => $this->format($user)),
            'roles' => Role::query()->orderBy('label')->get(['id', 'name', 'label']),
            'permissions' => Permission::query()->orderBy('label')->get(['id', 'name', 'label']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->payload($request);

        $user = User::query()->create([
            'name' => $payload['name'],
            'username' => $payload['username'],
            'email' => $payload['email'],
            'password' => Hash::make($payload['password']),
            'active' => $payload['active'],
        ]);

        $user->roles()->sync($payload['roles']);

        return response()->json([
            'ok' => true,
            'data' => $this->format($user->load('roles:id,name,label')),
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $payload = $this->payload($request, $user);

        $user->update([
            'name' => $payload['name'],
            'username' => $payload['username'],
            'email' => $payload['email'],
            'active' => $payload['active'],
            ...($payload['password'] ? ['password' => Hash::make($payload['password'])] : []),
        ]);

        $user->roles()->sync($payload['roles']);

        return response()->json([
            'ok' => true,
            'data' => $this->format($user->refresh()->load('roles:id,name,label')),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()->is($user)) {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes eliminar tu propio usuario.',
            ], 422);
        }

        $user->roles()->detach();
        $user->delete();

        return response()->json(['ok' => true]);
    }

    private function payload(Request $request, ?User $user = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:60', Rule::unique('users')->ignore($user?->id)],
            'email' => ['required', 'email', 'max:120', Rule::unique('users')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'max:120'],
            'active' => ['boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        return [
            ...$validated,
            'active' => (bool) ($validated['active'] ?? true),
        ];
    }

    private function format(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'active' => $user->active,
            'roles' => $user->roles->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => $role->label,
            ])->values(),
        ];
    }
}
