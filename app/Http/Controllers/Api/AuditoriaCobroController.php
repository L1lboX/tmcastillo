<?php

namespace App\Http\Controllers\Api;

use App\Traits\GeneratesExcel;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Models\CuentaCorriente;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditoriaCobroController extends Controller
    
{
    use GeneratesExcel;
   public function index(Request $request): JsonResponse
    {
        $fechaInicio = $request->query('fecha_inicio');
        $fechaFin    = $request->query('fecha_fin');
        $clienteId   = $request->query('cliente_id');
        $buscar      = trim((string) $request->query('buscar', ''));

        $query = CuentaCorriente::query()->where('tipo', 'abono');

        if ($clienteId) {
            $query->where('cliente_id', (int) $clienteId);
        }

        if ($buscar !== '') {
            $query->whereHas('cliente', function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('dni', 'like', "%{$buscar}%");
            });
        }

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('fecha', [
                Carbon::parse($fechaInicio)->toDateString(),
                Carbon::parse($fechaFin)->toDateString(),
            ]);
        }

        // 1. KPIs calculados sobre los mismos filtros (histórico total si no hay rango de fechas)
        $kpiQuery = (clone $query);

        $totalRecaudado   = (float) $kpiQuery->sum('monto');
        $abonosRealizados = (int) $kpiQuery->count();
        $clientesAbonaron = (int) $kpiQuery->distinct('cliente_id')->count('cliente_id');

        // 2. Relaciones y paginación para la tabla
        $abonos = $query->with([
            'cliente:id,dni,nombre,telefono',
            'envio:id,codigo,guia',
        ])
        ->latest('fecha')
        ->latest('id')
        ->paginate(15);

        return response()->json([
            'ok'   => true,
            'kpis' => [
                'total_recaudado'   => round($totalRecaudado, 2),
                'abonos_realizados' => $abonosRealizados,
                'clientes_abonaron' => $clientesAbonaron,
            ],
            'data' => $abonos,
        ]);
    }

    public function autocompletarClientes(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['ok' => true, 'data' => []]);
        }

        $clientes = \App\Models\Cliente::query()
            ->where('nombre', 'like', "%{$q}%")
            ->orWhere('dni', 'like', "%{$q}%")
            ->select('id', 'nombre', 'dni')
            ->limit(5)
            ->get();

        return response()->json(['ok' => true, 'data' => $clientes]);
    }


    public function export(Request $request): Response
    {
        $fechaInicio = $request->query('fecha_inicio');
        $fechaFin    = $request->query('fecha_fin');
        $clienteId   = $request->query('cliente_id');
        $buscar      = trim((string) $request->query('buscar', ''));

        $query = CuentaCorriente::with([
            'cliente:id,dni,nombre,telefono',
            'envio:id,codigo,guia',
        ])
        ->where('tipo', 'abono')
        ->latest('fecha')
        ->latest('id');

        if ($clienteId) {
            $query->where('cliente_id', (int) $clienteId);
        }

        if ($buscar !== '') {
            $query->whereHas('cliente', function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('dni', 'like', "%{$buscar}%");
            });
        }

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('fecha', [
                Carbon::parse($fechaInicio)->toDateString(),
                Carbon::parse($fechaFin)->toDateString(),
            ]);
        }

        $headers = [
            'ID',
            'Fecha',
            'Cliente',
            'DNI / Documento',
            'Telefono',
            'Guia / Envio',
            'Metodo Pago',
            'Monto Cobrado',
            'Detalle / Glosa',
        ];

        $abonos = $query->get();

        $rows = $abonos->map(function (CuentaCorriente $mov): array {
            $guia = $mov->envio?->guia ?? $mov->envio?->codigo ?? '-';

            return [
                $mov->id,
                $mov->fecha ? Carbon::parse($mov->fecha)->format('d/m/Y') : '-',
                $mov->cliente?->nombre ?? 'Sin cliente',
                $mov->cliente?->dni ?? '-',
                $mov->cliente?->telefono ?? '-',
                $guia,
                $mov->metodo_pago ?? 'Efectivo',
                number_format((float) $mov->monto, 2, '.', ''),
                $mov->glosa ?? $mov->detalle ?? '-',
            ];
        });

        $content = $this->xlsx($headers, $rows->all(), 'AuditoriaCobros', 'TablaCobros');
        $filename = 'auditoria_cobros_'.now()->format('Y-m-d_H-i-s').'.xlsx';

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($content),
        ]);
    }
}