<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Envio;
use App\Models\Transportista;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransportistaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('search', ''));
        $activeOnly = $request->boolean('active_only');
        $tipoFilter = trim((string) $request->query('tipo', ''));

        $transportistas = Transportista::query()
            ->when($activeOnly, fn ($q) => $q->where('activo', true))
            ->when($tipoFilter !== '', fn ($q) => $q->whereJsonContains('tipos', $tipoFilter))
            ->when($term !== '', function ($query) use ($term): void {
                $query->where('nombre', 'like', "%{$term}%")
                    ->orWhere('documento', 'like', "%{$term}%");
            })
            ->orderBy($term === '' ? 'created_at' : 'nombre', $term === '' ? 'desc' : 'asc')
            ->limit($term === '' ? 10 : 20)
            ->get();

        return response()->json(['ok' => true, 'data' => $transportistas]);
    }

    public function show(Transportista $transportista): JsonResponse
    {
        return response()->json(['ok' => true, 'data' => $transportista]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'documento' => ['nullable', 'string', 'max:20'],
            'tipo_documento' => ['nullable', 'string', 'in:dni,ruc'],
            'tipos' => ['nullable', 'array'],
            'tipos.*' => ['string', 'in:encomiendas,maquinaria,camion'],
        ]);

        return response()->json([
            'ok' => true,
            'data' => Transportista::query()->create([
                'nombre' => trim($payload['nombre']),
                'telefono' => trim((string) ($payload['telefono'] ?? '')) ?: null,
                'documento' => trim((string) ($payload['documento'] ?? '')) ?: null,
                'tipo_documento' => $payload['tipo_documento'] ?? null,
                'tipos' => $payload['tipos'] ?? null,
                'activo' => true,
            ]),
        ], 201);
    }

    public function update(Request $request, Transportista $transportista): JsonResponse
    {
        $payload = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'documento' => ['nullable', 'string', 'max:20'],
            'tipo_documento' => ['nullable', 'string', 'in:dni,ruc'],
            'tipos' => ['nullable', 'array'],
            'tipos.*' => ['string', 'in:encomiendas,maquinaria,camion'],
        ]);

        $transportista->update([
            'nombre' => trim($payload['nombre']),
            'telefono' => trim((string) ($payload['telefono'] ?? '')) ?: null,
            'documento' => trim((string) ($payload['documento'] ?? '')) ?: null,
            'tipo_documento' => $payload['tipo_documento'] ?? null,
            'tipos' => $payload['tipos'] ?? null,
        ]);

        return response()->json([
            'ok' => true,
            'data' => $transportista->fresh(),
        ]);
    }

    public function destroy(Transportista $transportista): JsonResponse
    {
        $pendientes = $transportista->envios()
            ->where('costo_transportista', '>', 0)
            ->whereNull('liquidado_at')
            ->count();

        if ($pendientes > 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'transportista' => "No se puede eliminar. El transportista tiene {$pendientes} envío(s) pendiente(s) de liquidar.",
            ]);
        }

        $transportista->delete();

        return response()->json(['ok' => true]);
    }

    public function toggleActive(Transportista $transportista): JsonResponse
    {
        $transportista->update(['activo' => !$transportista->activo]);

        return response()->json([
            'ok' => true,
            'data' => $transportista->fresh(),
        ]);
    }

    public function metrics(): JsonResponse
    {
        $transportistas = Transportista::query()
            ->get()
            ->map(function (Transportista $t) {
                $enviosTotal = Envio::query()
                    ->where('transportista_id', $t->id)
                    ->count();

                $deudaPendiente = Envio::query()
                    ->where('transportista_id', $t->id)
                    ->whereNotNull('liquidado_at')
                    ->whereNull('pagado_transportista_at')
                    ->sum('costo_transportista');

                $liquidados = Envio::query()
                    ->where('transportista_id', $t->id)
                    ->whereNotNull('pagado_transportista_at')
                    ->count();

                return [
                    'id' => $t->id,
                    'envios_total' => $enviosTotal,
                    'deuda_pendiente' => round((float) $deudaPendiente, 2),
                    'liquidados' => $liquidados,
                ];
            });

        return response()->json([
            'ok' => true,
            'data' => $transportistas->keyBy('id'),
        ]);
    }

    public function envios(Transportista $transportista): JsonResponse
    {
        $envios = Envio::query()
            ->with(['cliente', 'items'])
            ->where('transportista_id', $transportista->id)
            ->latest('id')
            ->get()
            ->map(fn (Envio $envio) => [
                'id' => $envio->id,
                'codigo' => $envio->codigo,
                'fecha' => $envio->fecha?->format('Y-m-d'),
                'cliente' => $envio->cliente?->nombre,
                'items' => $envio->items->map(fn ($item) => [
                    'id' => $item->id,
                    'cantidad' => $item->cantidad,
                    'descripcion' => $item->descripcion,
                    'monto' => $item->monto,
                ])->values(),
                'costo_transportista' => $envio->costo_transportista,
                'estado' => $envio->estado ?? 'Registrado',
                'liquidado_at' => $envio->liquidado_at?->format('Y-m-d H:i:s'),
                'pagado_transportista_at' => $envio->pagado_transportista_at?->format('Y-m-d H:i:s'),
            ]);

        return response()->json([
            'ok' => true,
            'data' => $envios,
        ]);
    }
}
