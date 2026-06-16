<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Envio;
use App\Models\EnvioItem;
use App\Models\Transportista;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AsignacionCargaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $envios = Envio::with(['cliente', 'items.transportista'])
            ->whereHas('items', fn ($q) => $q->whereNull('transportista_id'))
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(fn (Envio $e) => [
                'id' => $e->id,
                'codigo' => $e->codigo,
                'fecha' => $e->fecha?->format('Y-m-d'),
                'cliente' => $e->cliente?->nombre,
                'total_items' => $e->items->count(),
                'items_sin_asignar' => $e->items->whereNull('transportista_id')->count(),
                'items_asignados' => $e->items->whereNotNull('transportista_id')->count(),
            ]);

        return response()->json(['ok' => true, 'envios' => $envios]);
    }

    public function show(Envio $envio): JsonResponse
    {
        $envio->load(['cliente', 'items.transportista']);
        $transportistas = Transportista::where('activo', true)
            ->whereJsonContains('tipos', 'encomiendas')
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'ok' => true,
            'envio' => $this->formatEnvio($envio),
            'transportistas' => $transportistas->map(fn (Transportista $t) => $this->formatTransportista($t)),
        ]);
    }

    public function asignar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => ['required', 'integer', 'exists:envio_items,id'],
            'transportista_id' => ['required', 'integer', 'exists:transportistas,id'],
        ]);

        $item = EnvioItem::findOrFail($validated['item_id']);

        $item->update([
            'transportista_id' => $validated['transportista_id'],
        ]);

        return response()->json([
            'ok' => true,
            'item' => $this->formatItem($item->fresh()),
        ]);
    }

    public function desasignar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => ['required', 'integer', 'exists:envio_items,id'],
        ]);

        $item = EnvioItem::findOrFail($validated['item_id']);
        $item->update(['transportista_id' => null]);

        return response()->json([
            'ok' => true,
            'item' => $this->formatItem($item->fresh()),
        ]);
    }

    public function bulkAsignar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['integer', 'exists:envio_items,id'],
            'transportista_id' => ['required', 'integer', 'exists:transportistas,id'],
        ]);

        DB::transaction(function () use ($validated): void {
            EnvioItem::whereIn('id', $validated['item_ids'])
                ->update(['transportista_id' => $validated['transportista_id']]);
        });

        $items = EnvioItem::whereIn('id', $validated['item_ids'])
            ->get()
            ->map(fn (EnvioItem $item) => $this->formatItem($item));

        return response()->json([
            'ok' => true,
            'items' => $items,
        ]);
    }

    public function bulkDesasignar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['integer', 'exists:envio_items,id'],
        ]);

        DB::transaction(function () use ($validated): void {
            EnvioItem::whereIn('id', $validated['item_ids'])
                ->update(['transportista_id' => null]);
        });

        $items = EnvioItem::whereIn('id', $validated['item_ids'])
            ->get()
            ->map(fn (EnvioItem $item) => $this->formatItem($item));

        return response()->json([
            'ok' => true,
            'items' => $items,
        ]);
    }

    public function asignarEnvio(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'envio_id' => ['required', 'integer', 'exists:envios,id'],
            'transportista_id' => ['required', 'integer', 'exists:transportistas,id'],
        ]);

        DB::transaction(function () use ($validated): void {
            EnvioItem::where('envio_id', $validated['envio_id'])
                ->update(['transportista_id' => $validated['transportista_id']]);

            Envio::where('id', $validated['envio_id'])
                ->update(['transportista_id' => $validated['transportista_id']]);
        });

        $envio = Envio::with(['cliente', 'items.transportista', 'transportista'])
            ->findOrFail($validated['envio_id']);

        return response()->json([
            'ok' => true,
            'envio' => $this->formatEnvio($envio),
        ]);
    }

    private function formatEnvio(Envio $envio): array
    {
        return [
            'id' => $envio->id,
            'codigo' => $envio->codigo,
            'fecha' => $envio->fecha?->format('Y-m-d'),
            'cliente' => $envio->cliente?->nombre,
            'transportista_id' => $envio->transportista_id,
            'transportista' => $envio->transportista?->nombre,
            'estado' => $envio->estado ?? 'Registrado',
            'items' => $envio->items->map(fn (EnvioItem $item) => $this->formatItem($item))->values(),
        ];
    }

    private function formatItem(EnvioItem $item): array
    {
        return [
            'id' => $item->id,
            'envio_id' => $item->envio_id,
            'cantidad' => $item->cantidad,
            'descripcion' => $item->descripcion,
            'monto' => $item->monto,
            'transportista_id' => $item->transportista_id,
            'transportista' => $item->transportista?->nombre,
        ];
    }

    private function formatTransportista(Transportista $t): array
    {
        return [
            'id' => $t->id,
            'nombre' => $t->nombre,
            'documento' => $t->documento,
            'telefono' => $t->telefono,
            'tipos' => $t->tipos ?? [],
        ];
    }
}
