<?php

use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\EnvioController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\TipoPaqueteController;
use App\Http\Controllers\Api\TransportistaController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
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
    Route::delete('/envios/{envio}', [EnvioController::class, 'destroy'])
        ->middleware('permission:envios.delete');

    Route::get('/clientes', [ClienteController::class, 'index'])
        ->middleware('permission:envios.create');
    Route::post('/clientes', [ClienteController::class, 'store'])
        ->middleware('permission:clientes.manage');

    Route::get('/transportistas', [TransportistaController::class, 'index'])
        ->middleware('permission:envios.create');
    Route::post('/transportistas', [TransportistaController::class, 'store'])
        ->middleware('permission:transportistas.manage');

    Route::get('/tipos-paquete', [TipoPaqueteController::class, 'index'])
        ->middleware('permission:envios.create');
    Route::get('/tipos-paquete/{tipoPaquete}', [TipoPaqueteController::class, 'show'])
        ->middleware('permission:tipos_paquete.manage');
    Route::post('/tipos-paquete', [TipoPaqueteController::class, 'store'])
        ->middleware('permission:tipos_paquete.manage');
    Route::put('/tipos-paquete/{tipoPaquete}', [TipoPaqueteController::class, 'update'])
        ->middleware('permission:tipos_paquete.manage');
    Route::delete('/tipos-paquete/{tipoPaquete}', [TipoPaqueteController::class, 'destroy'])
        ->middleware('permission:tipos_paquete.manage');

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
});
