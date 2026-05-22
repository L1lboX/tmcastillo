<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TipoPaquete;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TipoPaqueteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $activeOnly = $request->boolean('active_only');

        $items = TipoPaquete::query()
            ->when($search !== '', fn ($query) => $query->where('nombre', 'like', "%{$search}%"))
            ->when($activeOnly, fn ($query) => $query->where('activo', true))
            ->orderBy('nombre')
            ->get();

        return response()->json(['ok' => true, 'data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => TipoPaquete::query()->create($this->payload($request)),
        ], 201);
    }

    public function show(TipoPaquete $tipoPaquete): JsonResponse
    {
        return response()->json(['ok' => true, 'data' => $tipoPaquete]);
    }

    public function update(Request $request, TipoPaquete $tipoPaquete): JsonResponse
    {
        $tipoPaquete->update($this->payload($request, $tipoPaquete));

        return response()->json(['ok' => true, 'data' => $tipoPaquete->refresh()]);
    }

    public function destroy(TipoPaquete $tipoPaquete): JsonResponse
    {
        if ($tipoPaquete->envios()->exists()) {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes eliminar un tipo usado en envios. Puedes marcarlo inactivo.',
            ], 422);
        }

        $tipoPaquete->delete();

        return response()->json(['ok' => true]);
    }

    private function payload(Request $request, ?TipoPaquete $tipoPaquete = null): array
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:80', Rule::unique('tipo_paquetes')->ignore($tipoPaquete?->id)],
            'precio_transportista' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'activo' => ['boolean'],
        ]);

        return [
            'nombre' => trim($validated['nombre']),
            'precio_transportista' => $validated['precio_transportista'],
            'descripcion' => trim((string) ($validated['descripcion'] ?? '')) ?: null,
            'activo' => (bool) ($validated['activo'] ?? true),
        ];
    }
}
