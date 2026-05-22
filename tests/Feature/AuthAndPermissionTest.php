<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class AuthAndPermissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite is required for database permission tests.');
        }

        $this->artisan('migrate:fresh')->run();
    }

    public function test_admin_can_login_and_access_user_management(): void
    {
        $this->seed();

        $response = $this->post('/login', [
            'usuario' => 'admin',
            'password' => 'admin12345',
        ]);

        $response->assertRedirect('/sistema');
        $this->assertAuthenticated();
        $this->get('/api/users')->assertOk();
    }

    public function test_agent_cannot_manage_users_or_register_amounts(): void
    {
        $this->seed();

        $agent = User::query()->create([
            'name' => 'Agente Test',
            'username' => 'agente',
            'email' => 'agente@example.com',
            'password' => 'secret123',
            'active' => true,
        ]);
        $agent->roles()->sync([Role::query()->where('name', 'agente')->value('id')]);

        $this->actingAs($agent)
            ->get('/api/users')
            ->assertForbidden();

        $this->actingAs($agent)
            ->postJson('/api/envios', [
                'fecha' => now()->format('Y-m-d'),
                'cliente_dni' => '87654321',
                'cliente' => 'Cliente sin monto',
                'cantidad' => 1,
                'tipo' => 'Caja',
                'detalle' => '1 caja.',
                'guia' => 'T-TEST-001',
                'pago' => 'Pagado',
                'monto' => 150,
            ])
            ->assertCreated()
            ->assertJsonPath('data.monto', null);
    }
}
