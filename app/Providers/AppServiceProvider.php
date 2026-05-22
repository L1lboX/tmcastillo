<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        if (! $this->app->runningInConsole()) {
            return Permission::query()->pluck('name')->all();
        }

        return [
            'envios.view',
            'envios.create',
            'envios.update',
            'envios.delete',
            'envios.export',
            'envios.amounts',
            'clientes.manage',
            'transportistas.manage',
            'tipos_paquete.manage',
            'dashboard.view',
            'users.manage',
            'roles.manage',
        ];
    }
}
