<?php

use App\Http\Controllers\Api\AsignacionCargaController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\CuentaCorrienteController;
use App\Http\Controllers\Api\EnvioController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\TransportistaController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuditoriaPagoController;

Route::get('/', function () {
    return view('public.index');
})->name('home');

Route::post('/contacto', ContactController::class)
    ->middleware('throttle:6,1')
    ->name('contacto.store');

Route::get('/login', function () {
    if (Auth::check()) {
        return redirect()->route('sistema');
    }

    return view('auth.login');
})->name('login');

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'usuario' => ['required', 'string', 'max:120'],
        'password' => ['required', 'string', 'max:120'],
    ]);

    $login = $credentials['usuario'];
    $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

    if (Auth::attempt([$field => $login, 'password' => $credentials['password'], 'active' => true])) {
        $request->session()->regenerate();

        return redirect()->intended('/sistema');
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

Route::get('/sistema', function () {
    return view('envios.index');
})->middleware('auth')->name('sistema');

Route::get('/asignacion-carga', function () {
    return view('asignacion.index');
})->middleware('auth')->name('asignacion');

Route::middleware('auth')->prefix('api')->group(function (): void {
    Route::get('/me', [UserController::class, 'me']);

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

    Route::get('/dashboard/ganancias', [DashboardController::class, 'ganancias'])
        ->middleware('permission:envios.amounts');

    Route::get('/asignacion-carga', [AsignacionCargaController::class, 'index'])
        ->middleware('permission:envios.create');
    Route::get('/asignacion-carga/{envio}', [AsignacionCargaController::class, 'show'])
        ->middleware('permission:envios.create');
    Route::post('/asignacion-carga/asignar', [AsignacionCargaController::class, 'asignar'])
        ->middleware('permission:envios.create');
    Route::post('/asignacion-carga/desasignar', [AsignacionCargaController::class, 'desasignar'])
        ->middleware('permission:envios.create');
    Route::post('/asignacion-carga/bulk', [AsignacionCargaController::class, 'bulkAsignar'])
        ->middleware('permission:envios.create');
    Route::post('/asignacion-carga/bulk-desasignar', [AsignacionCargaController::class, 'bulkDesasignar'])
        ->middleware('permission:envios.create');
    Route::post('/asignacion-carga/envio', [AsignacionCargaController::class, 'asignarEnvio'])
        ->middleware('permission:envios.create');
    Route::post('/asignacion-carga/split', [AsignacionCargaController::class, 'splitItem'])
        ->middleware('permission:envios.create');
    Route::post('/asignacion-carga/merge', [AsignacionCargaController::class, 'mergeItems'])
    ->middleware('permission:envios.create');

    Route::get('/auditoria/transportistas', [AuditoriaPagoController::class, 'index']);
    Route::get('/transportistas/{transportista}/historial-pagos', [AuditoriaPagoController::class, 'porTransportista']);

    

    Route::middleware('permission:users.manage')->group(function (): void {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });

    Route::middleware('permission:roles.manage')->group(function (): void {
        Route::get('/roles', [RoleController::class, 'index']);
        Route::post('/roles', [RoleController::class, 'store']);
        Route::put('/roles/{role}', [RoleController::class, 'update']);
        Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
    });

    Route::middleware('permission:clientes.debt')->group(function (): void {
        Route::get('/cuentas-corrientes', [CuentaCorrienteController::class, 'index']);
        Route::get('/cuentas-corrientes/clientes-con-deuda', [CuentaCorrienteController::class, 'clientesConDeuda']);
        Route::get('/cuentas-corrientes/saldo/{cliente}', [CuentaCorrienteController::class, 'saldoCliente']);
        Route::post('/cuentas-corrientes/abono', [CuentaCorrienteController::class, 'registrarAbono']);
        Route::post('/cuentas-corrientes/sync-envio/{envio}', [CuentaCorrienteController::class, 'syncEnvioCredito']);
    });
});
