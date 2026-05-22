<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'telefono' => ['required', 'string', 'max:30'],
            'empresa' => ['nullable', 'string', 'max:140'],
            'servicio' => ['required', 'string', 'max:80'],
            'mensaje' => ['required', 'string', 'max:2000'],
        ]);

        Log::info('Nuevo contacto desde la landing Transporte Castillo.', [
            'contacto' => $payload,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Mensaje recibido correctamente.',
        ]);
    }
}
