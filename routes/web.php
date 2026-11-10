<?php

use App\Http\Controllers\Api\AlquilerController;
use App\Http\Controllers\Api\AuditoriaCobroController;
use App\Http\Controllers\Api\AuditoriaPagoController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\CuentaCorrienteController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EnvioController;
use App\Http\Controllers\Api\MaquinariaController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\TransportistaController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\ContactController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Rutas Públicas
Route::get('/', function () {
    return view('public.index');
})->name('home');

Route::post('/contacto', ContactController::class)
    ->middleware('throttle:6,1')
    ->name('contacto.store');

// Autenticación
Route::get('/login', function () {
    if (Auth::check()) {
        return redirect()->route('sistema');
    }

    return view('auth.login');
})->name('login');

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'usuario'  => ['required', 'string', 'max:120'],
        'password' => ['required', 'string', 'max:120'],
    ]);

    $login = $credentials['usuario'];
    $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

    if (Auth::attempt([$field => $login, 'password' => $credentials['password'], 'active' => true])) {
        $request->session()->regenerate();

        return redirect('/sistema');
    }

    return back()
        ->withErrors(['usuario' => 'Credenciales invalidas o usuario inactivo.'])
        ->onlyInput('usuario');
})->name('login.submit');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

// Vistas Web Blade protegidas (SPA Centralizada)
Route::middleware('auth')->group(function (): void {
    Route::get('/sistema', function () {
        return view('envios.index');
    })->name('sistema');

    Route::get('/asignacion-carga', function () {
        return view('asignacion.index');
    })->name('asignacion');

    // Redirecciones hacia la SPA para mantener soporte con enlaces directos
    Route::get('/maquinarias', function () {
        return redirect()->route('sistema', ['section' => 'maquinarias']);
    })->name('maquinarias');

    Route::get('/alquileres', function () {
        return redirect()->route('sistema', ['section' => 'alquileres']);
    })->name('alquileres');
});

// Rutas de API protegidas
Route::middleware('auth')->prefix('api')->group(function (): void {
    Route::get('/me', [UserController::class, 'me']);

    // Envíos y Estadísticas
    Route::middleware('permission:envios.view')->group(function (): void {
        Route::get('/envios', [EnvioController::class, 'index']);
        Route::get('/stats', [EnvioController::class, 'stats']);
    });

    Route::get('/envios/export', [EnvioController::class, 'export'])
        ->middleware('permission:envios.export');
    Route::get('/envios/{envio}', [EnvioController::class, 'show'])
        ->middleware('permission:envios.view');
    Route::post('/envios', [EnvioController::class, 'store'])
        ->middleware('permission:envios.create');
    Route::put('/envios/{envio}', [EnvioController::class, 'update'])
        ->middleware('permission:envios.update');
    Route::put('/envios/{envio}/liquidacion', [EnvioController::class, 'liquidar'])
        ->middleware('permission:envios.amounts');
    Route::patch('/envios/{envio}/estado', [EnvioController::class, 'cambiarEstado'])
        ->middleware('permission:envios.update');
    Route::patch('/envios/{envio}/pagar-transportista', [EnvioController::class, 'pagarTransportista'])
        ->middleware('permission:envios.amounts');
    Route::post('/envios/pagar-transportista-lote', [EnvioController::class, 'pagarTransportistaLote'])
        ->middleware('permission:envios.amounts');
    Route::get('/transportistas-con-deuda', [EnvioController::class, 'transportistasConDeuda'])
        ->middleware('permission:envios.amounts');
    Route::get('/transportistas/{transportista}/envios-pendientes', [EnvioController::class, 'enviosPendientesTransportista'])
        ->middleware('permission:envios.amounts');
    Route::delete('/envios/{envio}', [EnvioController::class, 'destroy'])
        ->middleware('permission:envios.delete');

    // Clientes
    Route::get('/clientes', [ClienteController::class, 'index'])
        ->middleware('permission:envios.create');
    Route::get('/clientes/{cliente}', [ClienteController::class, 'show'])
        ->middleware('permission:envios.create');
    Route::post('/clientes', [ClienteController::class, 'store'])
        ->middleware('permission:clientes.manage');
    Route::put('/clientes/{cliente}', [ClienteController::class, 'update'])
        ->middleware('permission:clientes.manage');
    Route::delete('/clientes/{cliente}', [ClienteController::class, 'destroy'])
        ->middleware('permission:clientes.delete');
    Route::patch('/clientes/{cliente}/toggle', [ClienteController::class, 'toggleActive'])
        ->middleware('permission:clientes.manage');

    // Transportistas
    Route::get('/transportistas', [TransportistaController::class, 'index'])
        ->middleware('permission:envios.create');
    Route::get('/transportistas/metrics', [TransportistaController::class, 'metrics'])
        ->middleware('permission:envios.amounts');
    Route::get('/transportistas/{transportista}', [TransportistaController::class, 'show'])
        ->middleware('permission:envios.create');
    Route::get('/transportistas/{transportista}/envios', [TransportistaController::class, 'envios'])
        ->middleware('permission:envios.amounts');
    Route::post('/transportistas', [TransportistaController::class, 'store'])
        ->middleware('permission:transportistas.manage');
    Route::put('/transportistas/{transportista}', [TransportistaController::class, 'update'])
        ->middleware('permission:transportistas.manage');
    Route::delete('/transportistas/{transportista}', [TransportistaController::class, 'destroy'])
        ->middleware('permission:transportistas.delete');
    Route::patch('/transportistas/{transportista}/toggle', [TransportistaController::class, 'toggleActive'])
        ->middleware('permission:transportistas.manage');

    // Maquinarias
    Route::get('/maquinarias', [MaquinariaController::class, 'index'])
        ->middleware('permission:envios.create');
    Route::get('/maquinarias/{maquinaria}', [MaquinariaController::class, 'show'])
        ->middleware('permission:envios.create');
    Route::post('/maquinarias', [MaquinariaController::class, 'store'])
        ->middleware('permission:clientes.manage');
    Route::put('/maquinarias/{maquinaria}', [MaquinariaController::class, 'update'])
        ->middleware('permission:clientes.manage');
    Route::patch('/maquinarias/{maquinaria}/toggle', [MaquinariaController::class, 'cambiarEstado'])
        ->middleware('permission:clientes.manage');
    Route::patch('/maquinarias/{maquinaria}/estado', [MaquinariaController::class, 'cambiarEstado'])
        ->middleware('permission:clientes.manage');

    // Alquileres
    Route::get('/alquileres/proyectos', [AlquilerController::class, 'proyectos'])
        ->middleware('permission:envios.view');
    Route::get('/alquileres', [AlquilerController::class, 'index'])
        ->middleware('permission:envios.view');
    Route::get('/alquileres/{alquiler}', [AlquilerController::class, 'show'])
        ->middleware('permission:envios.view');
    Route::post('/alquileres', [AlquilerController::class, 'store'])
        ->middleware('permission:envios.create');
    Route::put('/alquileres/{alquiler}', [AlquilerController::class, 'update'])
        ->middleware('permission:envios.update');

    // Auditorías
    Route::get('/auditoria/transportistas', [AuditoriaPagoController::class, 'index']);
    Route::get('/auditoria/cobros', [AuditoriaCobroController::class, 'index']);
    Route::get('/transportistas/{transportista}/historial-pagos', [AuditoriaPagoController::class, 'porTransportista']);
    Route::get('/auditoria/transportistas/buscar-rapido', [AuditoriaPagoController::class, 'autocompletarChoferes']);
    Route::get('/auditoria/clientes/buscar-rapido', [AuditoriaCobroController::class, 'autocompletarClientes']);
    Route::get('/auditoria/transportistas/export', [AuditoriaPagoController::class, 'export']);  
    Route::get('/auditoria/cobros/export', [AuditoriaCobroController::class, 'export']);

    // Dashboard
    Route::get('/dashboard/ganancias', [DashboardController::class, 'ganancias'])
        ->middleware('permission:envios.amounts');

    // Usuarios
    Route::middleware('permission:users.manage')->group(function (): void {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });

    // Roles
    Route::middleware('permission:roles.manage')->group(function (): void {
        Route::get('/roles', [RoleController::class, 'index']);
        Route::post('/roles', [RoleController::class, 'store']);
        Route::put('/roles/{role}', [RoleController::class, 'update']);
        Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
    });

    // Cuentas Corrientes
    Route::middleware('permission:clientes.debt')->group(function (): void {
        Route::get('/cuentas-corrientes', [CuentaCorrienteController::class, 'index']);
        Route::get('/cuentas-corrientes/clientes-con-deuda', [CuentaCorrienteController::class, 'clientesConDeuda']);
        Route::get('/cuentas-corrientes/saldo/{cliente}', [CuentaCorrienteController::class, 'saldoCliente']);
        Route::post('/cuentas-corrientes/abono', [CuentaCorrienteController::class, 'registrarAbono']);
        Route::post('/cuentas-corrientes/sync-envio/{envio}', [CuentaCorrienteController::class, 'syncEnvioCredito']);
        Route::post('/cuentas-corrientes/sync-alquiler/{alquiler}', [CuentaCorrienteController::class, 'syncAlquilerCredito']);
    });
});