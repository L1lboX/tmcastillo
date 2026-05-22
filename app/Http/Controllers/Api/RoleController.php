<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));

        return response()->json([
            'ok' => true,
            'data' => Role::query()
                ->with('permissions:id,name,label')
                ->withCount('users')
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('label', 'like', "%{$search}%");
                })
                ->orderBy('label')
                ->get()
                ->map(fn (Role $role) => $this->format($role)),
            'permissions' => Permission::query()->orderBy('name')->get(['id', 'name', 'label']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->payload($request);

        $role = Role::query()->create([
            'name' => $payload['name'],
            'label' => $payload['label'],
        ]);
        $role->permissions()->sync($payload['permissions']);

        return response()->json([
            'ok' => true,
            'data' => $this->format($role->load('permissions:id,name,label')),
        ], 201);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $payload = $this->payload($request, $role);

        $role->update([
            'name' => $payload['name'],
            'label' => $payload['label'],
        ]);
        $role->permissions()->sync($payload['permissions']);

        return response()->json([
            'ok' => true,
            'data' => $this->format($role->refresh()->load('permissions:id,name,label')),
        ]);
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($role->name === 'super_admin') {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes eliminar el rol super admin.',
            ], 422);
        }

        if ($role->users()->exists()) {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes eliminar un rol asignado a usuarios.',
            ], 422);
        }

        $role->permissions()->detach();
        $role->delete();

        return response()->json(['ok' => true]);
    }

    private function payload(Request $request, ?Role $role = null): array
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9_]+$/', Rule::unique('roles')->ignore($role?->id)],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ], [
            'name.regex' => 'El identificador del rol solo puede usar minusculas, numeros y guion bajo.',
        ]);

        return [
            ...$validated,
            'permissions' => $validated['permissions'] ?? [],
        ];
    }

    private function format(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'label' => $role->label,
            'created_at' => $role->created_at?->format('Y-m-d H:i:s'),
            'users_count' => $role->users_count ?? $role->users()->count(),
            'permissions' => $role->permissions
                ->sortBy('name')
                ->map(fn (Permission $permission) => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'label' => $permission->label,
                ])
                ->values(),
        ];
    }
}
