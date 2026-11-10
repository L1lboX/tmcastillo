<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Maquinaria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaquinariaController extends Controller
{
    public function index(Request $request): JsonResponse{
        $q = trim((string) $request->query('q', ''));
        $soloActivos = $request->boolean('solo_activos', false);

        $maquinarias = Maquinaria::query()
            ->when($soloActivos, fn ($query) => $query->where('activo', true))
            ->when($q !== '', fn ($query) => $query->where('placa', 'like', "%{$q}%"))
            ->orderBy('placa', 'asc')
            ->get()
            ->map(fn (Maquinaria $m) => $this->format($m));

        return response()->json([
            'ok' => true,
            'data' => $maquinarias,
        ]);
    }

    public function show(Maquinaria $maquinaria): JsonResponse{
        return response()->json([
            'ok' => true,
            'data' => $this->format($maquinaria),
        ]);
    }

    public function store(Request $request): JsonResponse{
        $validated = $request->validate([
            'placa' => ['required', 'string', 'max:20', 'unique:maquinarias,placa'],
            'descripcion' => ['nullable', 'string', 'max:150'],
            'activo' => ['nullable', 'boolean'],
        ], [
            'placa.required' => 'La placa o código de la maquinaria es obligatorio.',
            'placa.unique' => 'Ya existe una maquinaria registrada con esta placa.',
        ]);

        $maquinaria = Maquinaria::create([
            'placa' => strtoupper(trim($validated['placa'])),
            'descripcion' => trim((string) ($validated['descripcion'] ?? '')) ?: null,
            'activo' => $validated['activo'] ?? true,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Maquinaria registrada con éxito.',
            'data' => $this->format($maquinaria),
        ], 201);
    }

    public function update(Request $request, Maquinaria $maquinaria): JsonResponse{
        $validated = $request->validate([
            'placa' => [
                'required',
                'string',
                'max:20',
                Rule::unique('maquinarias', 'placa')->ignore($maquinaria->id),
            ],
            'descripcion' => ['nullable', 'string', 'max:150'],
            'activo' => ['nullable', 'boolean'],
        ], [
            'placa.required' => 'La placa o código de la maquinaria es obligatorio.',
            'placa.unique' => 'Ya existe otra maquinaria con esta placa.',
        ]);

        $maquinaria->update([
            'placa' => strtoupper(trim($validated['placa'])),
            'descripcion' => trim((string) ($validated['descripcion'] ?? '')) ?: null,
            'activo' => $validated['activo'] ?? $maquinaria->activo,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Maquinaria actualizada con éxito.',
            'data' => $this->format($maquinaria->refresh()),
        ]);
    }

    public function cambiarEstado(Maquinaria $maquinaria): JsonResponse{
        $maquinaria->activo = ! $maquinaria->activo;
        $maquinaria->save();

        $estadoTexto = $maquinaria->activo ? 'activada' : 'desactivada';

        return response()->json([
            'ok' => true,
            'message' => "Maquinaria {$estadoTexto} correctamente.",
            'data' => $this->format($maquinaria),
        ]);
    }

    private function format(Maquinaria $m): array{
        return [
            'id'          => $m->id,
            'placa'       => $m->placa,
            'descripcion' => $m->descripcion,
            'activo'      => (bool) $m->activo,
            'created_at'  => $m->created_at?->format('Y-m-d H:i:s'),
        ];
    }

}