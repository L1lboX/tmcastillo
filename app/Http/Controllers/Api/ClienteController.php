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

    public function show(Cliente $cliente): JsonResponse
    {
        return response()->json(['ok' => true, 'data' => $cliente]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validatedPayload($request);

        if ($payload['dni'] !== null && Cliente::query()->where('dni', $payload['dni'])->exists()) {
            throw ValidationException::withMessages([
                'dni' => 'Ya existe un cliente con ese documento.',
            ]);
        }

        return response()->json([
            'ok' => true,
            'data' => Cliente::query()->create($payload),
        ], 201);
    }

    public function update(Request $request, Cliente $cliente): JsonResponse
    {
        $payload = $this->validatedPayload($request, $cliente->id);

        if ($payload['dni'] !== null && Cliente::query()->where('dni', $payload['dni'])->where('id', '!=', $cliente->id)->exists()) {
            throw ValidationException::withMessages([
                'dni' => 'Ya existe otro cliente con ese documento.',
            ]);
        }

        $cliente->update($payload);

        return response()->json([
            'ok' => true,
            'data' => $cliente->fresh(),
        ]);
    }

    public function destroy(Cliente $cliente): JsonResponse
    {
        $cliente->delete();

        return response()->json(['ok' => true]);
    }

    public function toggleActive(Cliente $cliente): JsonResponse
    {
        $cliente->update(['activo' => !$cliente->activo]);

        return response()->json([
            'ok' => true,
            'data' => $cliente->fresh(),
        ]);
    }

    public static function upsertFromEnvio(Request $request): Cliente
    {
        $payload = self::payloadFromRequest($request);

        if ($payload['dni'] !== null) {
            return Cliente::query()->updateOrCreate(
                ['dni' => $payload['dni']],
                $payload,
            );
        }

        return Cliente::query()->firstOrCreate(
            ['nombre' => $payload['nombre']],
            $payload,
        );
    }

    private function validatedPayload(Request $request, ?int $excludeId = null): array
    {
        return self::payloadFromRequest($request);
    }

    private static function payloadFromRequest(Request $request): array
    {
        $tipoDocumento = trim((string) ($request->input('tipo_documento', '')));
        $dni = trim((string) ($request->input('dni') ?? $request->input('cliente_dni', '')));
        $nombre = trim((string) ($request->input('nombre') ?? $request->input('cliente', '')));

        if ($tipoDocumento !== '' && !in_array($tipoDocumento, ['dni', 'ruc'], true)) {
            throw ValidationException::withMessages([
                'tipo_documento' => 'El tipo de documento debe ser DNI o RUC.',
            ]);
        }

        if ($dni !== '') {
            if ($tipoDocumento === 'dni') {
                if (strlen($dni) !== 8 || !preg_match('/^\d+$/', $dni)) {
                    throw ValidationException::withMessages([
                        'dni' => 'El DNI debe tener 8 dígitos numéricos.',
                    ]);
                }
            } elseif ($tipoDocumento === 'ruc') {
                if (strlen($dni) !== 11 || !preg_match('/^\d+$/', $dni)) {
                    throw ValidationException::withMessages([
                        'dni' => 'El RUC debe tener 11 dígitos numéricos.',
                    ]);
                }
            } elseif ($tipoDocumento === '') {
                if (!preg_match('/^\d+$/', $dni) || !in_array(strlen($dni), [8, 11], true)) {
                    throw ValidationException::withMessages([
                        'dni' => 'El documento debe tener 8 dígitos (DNI) o 11 dígitos (RUC).',
                    ]);
                }
                $tipoDocumento = strlen($dni) === 8 ? 'dni' : 'ruc';
            }
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
            'dni' => $dni === '' ? null : $dni,
            'tipo_documento' => $dni !== '' ? $tipoDocumento : null,
            'nombre' => $nombre,
            'telefono' => $telefono ?: null,
            'direccion' => trim((string) $request->input('direccion', '')) ?: null,
        ];
    }
}
