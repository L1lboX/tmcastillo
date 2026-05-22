<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transportista;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransportistaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('search', ''));

        $transportistas = Transportista::query()
            ->when($term !== '', function ($query) use ($term): void {
                $query->where('nombre', 'like', "%{$term}%")
                    ->orWhere('documento', 'like', "%{$term}%");
            })
            ->where('activo', true)
            ->orderBy('nombre')
            ->limit($term === '' ? 50 : 20)
            ->get();

        return response()->json(['ok' => true, 'data' => $transportistas]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'documento' => ['nullable', 'string', 'max:20'],
        ]);

        return response()->json([
            'ok' => true,
            'data' => Transportista::query()->create([
                'nombre' => trim($payload['nombre']),
                'telefono' => trim((string) ($payload['telefono'] ?? '')) ?: null,
                'documento' => trim((string) ($payload['documento'] ?? '')) ?: null,
                'activo' => true,
            ]),
        ], 201);
    }
}
