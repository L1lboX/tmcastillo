<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user): ?bool {
            return $user->hasRole('super_admin') ? true : null;
        });

        foreach ($this->permissions() as $permission) {
            Gate::define($permission, fn (User $user): bool => $user->hasPermission($permission));
        }
    }

    /**
     * @return array<int, string>
     */
    private function permissions(): array
    {
        $permissions = [
            'envios.view',
            'envios.create',
            'envios.update',
            'envios.delete',
            'envios.export',
            'envios.amounts',
            'clientes.manage',
            'clientes.delete',
            'transportistas.manage',
            'transportistas.delete',
            'tipos_paquete.manage',
            'dashboard.view',
            'users.manage',
            'roles.manage',
        ];

        if ($this->app->runningInConsole()) {
            return $permissions;
        }

        try {
            return Permission::query()->pluck('name')->all();
        } catch (Throwable $exception) {
            report($exception);

            return $permissions;
        }
    }
}
