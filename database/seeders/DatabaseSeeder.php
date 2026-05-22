<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Permission;
use App\Models\Role;
use App\Models\TipoPaquete;
use App\Models\Transportista;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $permissions = [
            'envios.view' => 'Ver envios',
            'envios.create' => 'Crear envios',
            'envios.update' => 'Editar envios',
            'envios.delete' => 'Eliminar envios',
            'envios.export' => 'Exportar envios',
            'envios.amounts' => 'Registrar montos',
            'clientes.manage' => 'Gestionar clientes',
            'transportistas.manage' => 'Gestionar transportistas',
            'tipos_paquete.manage' => 'Gestionar tipos de paquete',
            'dashboard.view' => 'Ver dashboard',
            'users.manage' => 'Gestionar usuarios',
            'roles.manage' => 'Gestionar roles y permisos',
        ];

        $permissionModels = collect($permissions)
            ->mapWithKeys(fn (string $label, string $name) => [
                $name => Permission::query()->updateOrCreate(['name' => $name], ['label' => $label]),
            ]);

        $roles = [
            'agente' => [
                'label' => 'Agente',
                'permissions' => ['envios.view', 'envios.create', 'envios.update', 'clientes.manage', 'transportistas.manage'],
            ],
            'contador' => [
                'label' => 'Contador',
                'permissions' => ['envios.view', 'envios.export', 'envios.amounts', 'dashboard.view'],
            ],
            'transportista' => [
                'label' => 'Transportista',
                'permissions' => ['envios.view'],
            ],
            'administrador' => [
                'label' => 'Administrador',
                'permissions' => ['envios.view', 'envios.create', 'envios.update', 'envios.delete', 'envios.export', 'envios.amounts', 'clientes.manage', 'transportistas.manage', 'tipos_paquete.manage', 'dashboard.view', 'users.manage', 'roles.manage'],
            ],
            'super_admin' => [
                'label' => 'Super admin',
                'permissions' => array_keys($permissions),
            ],
        ];

        $roleModels = collect($roles)->mapWithKeys(function (array $data, string $name) use ($permissionModels) {
            $role = Role::query()->updateOrCreate(['name' => $name], ['label' => $data['label']]);
            $role->permissions()->sync(
                collect($data['permissions'])->map(fn (string $permission) => $permissionModels[$permission]->id)->all(),
            );

            return [$name => $role];
        });

        $admin = User::query()->updateOrCreate([
            'email' => env('ADMIN_EMAIL', 'admin@transportescastillo.local'),
        ], [
            'name' => env('ADMIN_NAME', 'Super Admin'),
            'username' => env('ADMIN_USERNAME', 'admin'),
            'password' => Hash::make(env('ADMIN_PASSWORD', 'admin12345')),
            'active' => true,
        ]);
        $admin->roles()->syncWithoutDetaching([$roleModels['super_admin']->id]);

        collect([
            ['nombre' => 'Caja', 'precio_transportista' => 5],
            ['nombre' => 'Quintal', 'precio_transportista' => 8],
            ['nombre' => 'Saco', 'precio_transportista' => 6],
            ['nombre' => 'Plancha', 'precio_transportista' => 7],
            ['nombre' => 'Bidon', 'precio_transportista' => 5],
            ['nombre' => 'Galon', 'precio_transportista' => 4],
            ['nombre' => 'Ropero', 'precio_transportista' => 15],
            ['nombre' => 'Colchon', 'precio_transportista' => 12],
            ['nombre' => 'Paquete', 'precio_transportista' => 5],
            ['nombre' => 'Bolsa', 'precio_transportista' => 3],
        ])->each(fn (array $tipo) => TipoPaquete::query()->updateOrCreate(
            ['nombre' => $tipo['nombre']],
            [
                'precio_transportista' => $tipo['precio_transportista'],
                'activo' => true,
            ],
        ));

        Cliente::query()->updateOrCreate([
            'dni' => '12345678',
        ], [
            'nombre' => 'Cliente Demo',
            'telefono' => '987654321',
            'direccion' => 'Lima',
        ]);

        Transportista::query()->updateOrCreate([
            'nombre' => 'Transportista Demo',
        ], [
            'telefono' => '999888777',
            'documento' => 'T-001',
            'activo' => true,
        ]);
    }
}
