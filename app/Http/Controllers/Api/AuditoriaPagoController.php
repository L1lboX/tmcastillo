<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PagoTransportista;
use App\Models\Transportista;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditoriaPagoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $fechaInicio = $request->query('fecha_inicio');
        $fechaFin    = $request->query('fecha_fin');
        $transportistaId = $request->query('transportista_id');

        // Query base para el listado
        $query = PagoTransportista::with([
            'transportista:id,nombre,documento,telefono',
            'user:id,name',
            'items.envio:id,codigo,guia,cliente_id',
            'items.envio.cliente:id,nombre',
        ])->latest('fecha_pago');

        if ($transportistaId) {
            $query->where('transportista_id', $transportistaId);
        }

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('fecha_pago', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay(),
            ]);
        }

        // Paginación limpia de 15 registros por página
        $pagos = $query->paginate(15);

        // KPIs calculados sobre el período filtrado (o mes actual por defecto)
        $kpiQuery = PagoTransportista::query();
        if ($fechaInicio && $fechaFin) {
            $kpiQuery->whereBetween('fecha_pago', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay(),
            ]);
        } else {
            $kpiQuery->whereMonth('fecha_pago', now()->month)
                     ->whereYear('fecha_pago', now()->year);
        }

        $totalDesembolsado = (float) $kpiQuery->sum('monto_total');
        $abonosEfectuados  = (int) $kpiQuery->count();
        $choferesDistintos = (int) $kpiQuery->distinct('transportista_id')->count('transportista_id');

        return response()->json([
            'ok'   => true,
            'kpis' => [
                'total_desembolsado' => round($totalDesembolsado, 2),
                'abonos_efectuados'  => $abonosEfectuados,
                'choferes_saldados'  => $choferesDistintos,
            ],
            'data' => $pagos,
        ]);
    }

    public function porTransportista(Transportista $transportista): JsonResponse
    {
        $pagos = PagoTransportista::with([
            'user:id,name',
            'items.envio:id,codigo,guia,cliente_id',
            'items.envio.cliente:id,nombre',
        ])
        ->where('transportista_id', $transportista->id)
        ->latest('fecha_pago')
        ->paginate(15);

        $historicoTotal = (float) PagoTransportista::where('transportista_id', $transportista->id)->sum('monto_total');

        return response()->json([
            'ok'              => true,
            'transportista'   => [
                'id'        => $transportista->id,
                'nombre'    => $transportista->nombre,
                'documento' => $transportista->documento,
            ],
            'historico_total' => round($historicoTotal, 2),
            'data'            => $pagos,
        ]);
    }
}
