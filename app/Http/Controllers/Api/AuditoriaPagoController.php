<?php

namespace App\Http\Controllers\Api;

use App\Traits\GeneratesExcel;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Models\PagoTransportista;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class AuditoriaPagoController extends Controller
    
{
    use GeneratesExcel;
    public function index(Request $request): JsonResponse
    {
        $fechaInicio     = $request->query('fecha_inicio');
        $fechaFin        = $request->query('fecha_fin');
        $transportistaId = $request->query('transportista_id');
        $buscar          = trim((string) $request->query('buscar', ''));

        $query = PagoTransportista::query();

        if ($transportistaId) {
            $query->where('transportista_id', (int) $transportistaId);
        }

        if ($buscar !== '') {
            $query->whereHas('transportista', function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('documento', 'like', "%{$buscar}%");
            });
        }

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('fecha_pago', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay(),
            ]);
        }

        // 1. KPIs calculados exactamente sobre los mismos filtros aplicados (sin forzar mes actual)
        $kpiQuery = (clone $query);

        $totalDesembolsado = (float) $kpiQuery->sum('monto_total');
        $abonosEfectuados  = (int) $kpiQuery->count();
        $choferesDistintos = (int) $kpiQuery->distinct('transportista_id')->count('transportista_id');

        // 2. Carga de relaciones y ordenamiento solo para la paginación de la tabla
        $pagos = $query->with([
            'transportista:id,nombre,documento,telefono',
            'user:id,name',
            'items.envio:id,codigo,guia,cliente_id',
            'items.envio.cliente:id,nombre',
        ])
        ->latest('fecha_pago')
        ->paginate(15);

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

    public function porTransportista(int $transportistaId): JsonResponse
    {
        $pagos = PagoTransportista::with([
            'user:id,name',
            'items.envio:id,codigo,guia',
        ])
        ->where('transportista_id', $transportistaId)
        ->latest('fecha_pago')
        ->paginate(15);

        return response()->json([
            'ok'   => true,
            'data' => $pagos,
        ]);
    }

    public function autocompletarChoferes(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['ok' => true, 'data' => []]);
        }

        $choferes = \App\Models\Transportista::query()
            ->where('nombre', 'like', "%{$q}%")
            ->orWhere('documento', 'like', "%{$q}%")
            ->select('id', 'nombre', 'documento')
            ->limit(5)
            ->get();

        return response()->json(['ok' => true, 'data' => $choferes]);
    }

    public function export(Request $request): Response
    {
        $fechaInicio     = $request->query('fecha_inicio');
        $fechaFin        = $request->query('fecha_fin');
        $transportistaId = $request->query('transportista_id');
        $buscar          = trim((string) $request->query('buscar', ''));

        $query = PagoTransportista::with([
            'transportista:id,nombre,documento,telefono',
            'user:id,name',
            'items.envio:id,codigo,guia',
        ])->latest('fecha_pago');

        if ($transportistaId) {
            $query->where('transportista_id', (int) $transportistaId);
        }

        if ($buscar !== '') {
            $query->whereHas('transportista', function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('documento', 'like', "%{$buscar}%");
            });
        }

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('fecha_pago', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay(),
            ]);
        }

        $headers = [
            'ID Recibo',
            'Fecha Pago',
            'Transportista',
            'Documento',
            'Telefono',
            'Registrado Por',
            'Guias / Envios',
            'Total Envios',
            'Monto Pagado',
        ];

        $pagos = $query->get();

        $rows = $pagos->map(function (PagoTransportista $pago): array {
            $guias = $pago->items->map(function ($item) {
                return $item->envio?->guia ?? $item->envio?->codigo ?? null;
            })->filter()->implode(', ');

            return [
                $pago->id,
                $pago->fecha_pago ? Carbon::parse($pago->fecha_pago)->format('d/m/Y H:i') : '-',
                $pago->transportista?->nombre ?? 'Sin transportista',
                $pago->transportista?->documento ?? '-',
                $pago->transportista?->telefono ?? '-',
                $pago->user?->name ?? 'Sistema',
                $guias ?: '-',
                $pago->items->count(),
                number_format((float) $pago->monto_total, 2, '.', ''),
            ];
        });

        $content = $this->xlsx($headers, $rows->all(), 'AuditoriaPagos', 'TablaPagos');
        $filename = 'auditoria_pagos_'.now()->format('Y-m-d_H-i-s').'.xlsx';

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($content),
        ]);
    }
}