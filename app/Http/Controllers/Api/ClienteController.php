<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ClienteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('search', ''));

        $clientes = Cliente::query()
            ->when($term !== '', function ($query) use ($term): void {
                $query->where('dni', 'like', "%{$term}%")
                    ->orWhere('nombre', 'like', "%{$term}%");
            })
            ->orderBy('nombre')
            ->limit($term === '' ? 50 : 20)
            ->get();

        return response()->json(['ok' => true, 'data' => $clientes]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validatedPayload($request);

        if (Cliente::query()->whereKey($payload['dni'])->exists()) {
            throw ValidationException::withMessages([
                'dni' => 'El cliente ya existe.',
            ]);
        }

        return response()->json([
            'ok' => true,
            'data' => Cliente::query()->create($payload),
        ], 201);
    }

    public static function upsertFromEnvio(Request $request): Cliente
    {
        $payload = self::payloadFromRequest($request);

        return Cliente::query()->updateOrCreate(
            ['dni' => $payload['dni']],
            $payload,
        );
    }

    private function validatedPayload(Request $request): array
    {
        return self::payloadFromRequest($request);
    }

    private static function payloadFromRequest(Request $request): array
    {
        $dni = trim((string) ($request->input('dni') ?? $request->input('cliente_dni', '')));
        $nombre = trim((string) ($request->input('nombre') ?? $request->input('cliente', '')));

        if ($dni === '' || strlen($dni) !== 8 || !preg_match('/^\d+$/', $dni)) {
            throw ValidationException::withMessages([
                'dni' => 'El DNI debe tener 8 dígitos numéricos.',
            ]);
        }

        if ($nombre === '') {
            throw ValidationException::withMessages([
                'nombre' => 'El nombre del cliente es requerido.',
            ]);
        }

        $telefono = trim((string) $request->input('telefono', ''));
        if ($telefono !== '' && (!preg_match('/^\d+$/', $telefono) || strlen($telefono) !== 9)) {
            throw ValidationException::withMessages([
                'telefono' => 'El teléfono debe tener 9 dígitos numéricos.',
            ]);
        }

        return [
            'dni' => $dni,
            'nombre' => $nombre,
            'telefono' => $telefono ?: null,
            'direccion' => trim((string) $request->input('direccion', '')) ?: null,
        ];
    }
}
