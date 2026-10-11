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
            'costo_transportista' => $item->costo_transportista,
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

    public function splitItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => 'required|integer|exists:envio_items,id',
            'cantidades' => 'required|array|min:2',
            'cantidades.*' => 'required|integer|min:1',
        ]);

        $itemOriginal = EnvioItem::findOrFail($validated['item_id']);
        $cantidades = array_map('intval', $validated['cantidades']);
        $totalPartes = array_sum($cantidades);

        if ($totalPartes !== (int) $itemOriginal->cantidad) {
            return response()->json([
                'message' => "La suma de las partes ({$totalPartes}) no coincide con la cantidad actual del ítem ({$itemOriginal->cantidad}).",
            ], 422);
        }

        $itemsResultantes = DB::transaction(function () use ($itemOriginal, $cantidades) {
            $cantidadOriginal = (int) $itemOriginal->cantidad;
            $montoTotalOriginal = (float) ($itemOriginal->monto ?? 0);
            $costoTotalOriginal = (float) ($itemOriginal->costo_transportista ?? 0);
            if ($costoTotalOriginal <= 0 && $itemOriginal->envio?->costo_transportista > 0) {
                $envio = $itemOriginal->envio;
                $totalCantidadEnvio = (int) $envio->items()->sum('cantidad') ?: $cantidadOriginal;
                $costoTotalOriginal = round(((float) $envio->costo_transportista * $cantidadOriginal) / $totalCantidadEnvio, 2);
            }
            $itemsCreados = [];

            $primeraCantidad = array_shift($cantidades);
            $primerMonto = $cantidadOriginal > 0 
                ? round(($montoTotalOriginal * $primeraCantidad) / $cantidadOriginal, 2) 
                : 0.00;
            $primerCosto = $cantidadOriginal > 0
                ? round(($costoTotalOriginal * $primeraCantidad) / $cantidadOriginal, 2)
                : 0.00;

            $itemOriginal->update([
                'cantidad' => $primeraCantidad,
                'monto' => $primerMonto,
                'costo_transportista' => $primerCosto,
            ]);
            $itemsCreados[] = $itemOriginal->fresh(['transportista']);

            $montoAcumulado = $primerMonto;
            $costoAcumulado = $primerCosto;
            $totalPartesRestantes = count($cantidades);

            foreach ($cantidades as $index => $cant) {
                $esUltimo = ($index === $totalPartesRestantes - 1);

                if ($esUltimo) {
                    $montoFraccion = round($montoTotalOriginal - $montoAcumulado, 2);
                    $costoFraccion = round($costoTotalOriginal - $costoAcumulado, 2);
                } else {
                    $montoFraccion = $cantidadOriginal > 0 
                        ? round(($montoTotalOriginal * $cant) / $cantidadOriginal, 2) 
                        : 0.00;
                    $costoFraccion = $cantidadOriginal > 0
                        ? round(($costoTotalOriginal * $cant) / $cantidadOriginal, 2)
                        : 0.00;

                    $montoAcumulado += $montoFraccion;
                    $costoAcumulado += $costoFraccion;
                }

                $nuevoItem = EnvioItem::create([
                    'envio_id' => $itemOriginal->envio_id,
                    'transportista_id' => $itemOriginal->transportista_id,
                    'cantidad' => $cant,
                    'descripcion' => $itemOriginal->descripcion,
                    'monto' => $montoFraccion,
                    'costo_transportista' => $costoFraccion,
                ]);

                $itemsCreados[] = $nuevoItem->fresh(['transportista']);
            }

            return $itemsCreados;
        });

        return response()->json([
            'ok' => true,
            'message' => 'Ítem fraccionado correctamente.',
            'items' => collect($itemsResultantes)->map(fn ($it) => $this->formatItem($it)),
        ]);
    }


    public function mergeItems(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id_origen' => 'required|integer|exists:envio_items,id',
            'item_id_destino' => 'required|integer|exists:envio_items,id',
        ]);

        if ($validated['item_id_origen'] === $validated['item_id_destino']) {
            return response()->json(['message' => 'No puedes fusionar un ítem consigo mismo.'], 422);
        }

        $origen = EnvioItem::findOrFail($validated['item_id_origen']);
        $destino = EnvioItem::findOrFail($validated['item_id_destino']);

        $descOrigen = trim(mb_strtolower($origen->descripcion));
        $descDestino = trim(mb_strtolower($destino->descripcion));

        if ($origen->envio_id !== $destino->envio_id || $descOrigen !== $descDestino) {
            return response()->json([
                'message' => 'Los ítems deben pertenecer al mismo envío y tener la misma descripción.',
            ], 422);
        }

        DB::transaction(function () use ($origen, $destino) {
            $destino->update([
                'cantidad' => (int) $destino->cantidad + (int) $origen->cantidad,
                'monto' => round((float) $destino->monto + (float) $origen->monto, 2),
                'costo_transportista' => round((float) $destino->costo_transportista + (float) $origen->costo_transportista, 2),
            ]);

            $origen->delete();
        });

        return response()->json([
            'message' => 'Ítems fusionados exitosamente.',
        ]);
    }
}
