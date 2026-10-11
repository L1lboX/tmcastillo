<?php

namespace App\Http\Controllers\Api;
use App\Models\EnvioItem;
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
                // Envíos totales donde este transportista tiene al menos un ítem asignado
                $enviosTotal = EnvioItem::query()
                    ->where('transportista_id', $t->id)
                    ->distinct('envio_id')
                    ->count('envio_id');

                // Flete pendiente por abonar de ítems asignados en envíos ya liquidados
                $deudaPendiente = EnvioItem::query()
                    ->join('envios', 'envio_items.envio_id', '=', 'envios.id')
                    ->where('envio_items.transportista_id', $t->id)
                    ->whereNotNull('envios.liquidado_at')
                    ->whereNull('envios.pagado_transportista_at')
                    ->sum('envio_items.costo_transportista');

                // Envíos ya saldados
                $liquidados = EnvioItem::query()
                    ->join('envios', 'envio_items.envio_id', '=', 'envios.id')
                    ->where('envio_items.transportista_id', $t->id)
                    ->whereNotNull('envios.pagado_transportista_at')
                    ->distinct('envio_id')
                    ->count('envio_id');

                return [
                    'id'              => $t->id,
                    'envios_total'    => $enviosTotal,
                    'deuda_pendiente' => round((float) $deudaPendiente, 2),
                    'liquidados'      => $liquidados,
                ];
            });

        return response()->json([
            'ok'   => true,
            'data' => $transportistas->keyBy('id'),
        ]);
    }

    public function envios(Transportista $transportista): JsonResponse
    {
        $envios = Envio::query()
            ->with(['cliente', 'items' => function ($q) use ($transportista) {
                $q->where('transportista_id', $transportista->id);
            }])
            ->where(function ($q) use ($transportista) {
                $q->where('transportista_id', $transportista->id)
                  ->orWhereHas('items', fn ($sub) => $sub->where('transportista_id', $transportista->id));
            })
            ->latest('id')
            ->get()
            ->map(function (Envio $envio) use ($transportista) {
                $items = $envio->items;
                $tieneItems = $items->isNotEmpty();

                // Costo total del flete para este transportista
                $costoFlete = $tieneItems
                    ? (float) $items->sum('costo_transportista')
                    : (float) ($envio->costo_transportista ?? 0);

                // Costo pendiente real para este transportista
                $costoPendiente = $tieneItems
                    ? (float) $items->whereNull('pagado_transportista_at')->sum('costo_transportista')
                    : ($envio->pagado_transportista_at ? 0.0 : $costoFlete);

                // Determinar fecha de pago para este transportista
                $pagadoAt = $tieneItems
                    ? ($items->whereNull('pagado_transportista_at')->isEmpty() && $items->isNotEmpty()
                        ? $items->max('pagado_transportista_at')
                        : null)
                    : $envio->pagado_transportista_at;

                return [
                    'id'                      => $envio->id,
                    'codigo'                  => $envio->codigo,
                    'guia'                    => $envio->guia,
                    'fecha'                   => $envio->fecha?->format('Y-m-d'),
                    'cliente'                 => $envio->cliente?->nombre,
                    'monto'                   => (float) $envio->monto,
                    'costo_transportista'     => $costoFlete,
                    'costo_pendiente'         => $costoPendiente,
                    'estado'                  => $envio->estado ?? 'Registrado',
                    'liquidado_at'            => $envio->liquidado_at?->format('Y-m-d H:i:s'),
                    'pagado_transportista_at' => $pagadoAt ? \Carbon\Carbon::parse($pagadoAt)->format('Y-m-d H:i:s') : null,
                    'items'                   => $items->map(fn ($item) => [
                        'id'                      => $item->id,
                        'cantidad'                => $item->cantidad,
                        'descripcion'             => $item->descripcion,
                        'monto'                   => $item->monto,
                        'costo_transportista'     => $item->costo_transportista,
                        'pago_transportista_id'   => $item->pago_transportista_id,
                        'pagado_transportista_at' => $item->pagado_transportista_at?->format('Y-m-d H:i:s'),
                    ])->values(),
                ];
            });

        return response()->json([
            'ok'   => true,
            'data' => $envios,
        ]);
    }

    public function liquidacionSemanal($transportistaId, Request $request)
    {
        $fechaInicio = $request->query('fecha_inicio', now()->startOfWeek()->toDateString());
        $fechaFin    = $request->query('fecha_fin', now()->endOfWeek()->toDateString());

        $totalAPagarSemana = EnvioItem::where('transportista_id', $transportistaId)
            ->whereHas('envio', function ($query) use ($fechaInicio, $fechaFin) {
                $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
            })
            ->sum('costo_transportista');

        return response()->json([
            'transportista_id' => $transportistaId,
            'total_a_pagar'    => $totalAPagarSemana,
        ]);
    }
}
