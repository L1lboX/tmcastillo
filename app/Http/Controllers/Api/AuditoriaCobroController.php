<?php

namespace App\Http\Controllers\Api;

use App\Traits\GeneratesExcel;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Models\CuentaCorriente;
use App\Models\Cliente;
use App\Models\Alquiler;
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
        $servicio    = trim((string) $request->query('servicio', '')); // Recibe 'envios' o 'alquileres'

        $consolidado = collect();

        // 1. Obtener Envíos (si no se filtró exclusivamente por alquileres)
        if ($servicio === '' || $servicio === 'envios') {
            $enviosQuery = CuentaCorriente::with(['cliente', 'envio'])
                ->where('tipo', 'abono')
                ->whereNotNull('envio_id'); // Filtro estricto para los pagos de envíos

            if ($clienteId) {
                $enviosQuery->where('cliente_id', (int) $clienteId);
            }

            if ($buscar !== '') {
                $enviosQuery->whereHas('cliente', function ($q) use ($buscar) {
                    $q->where('nombre', 'like', "%{$buscar}%")->orWhere('dni', 'like', "%{$buscar}%");
                });
            }

            if ($fechaInicio && $fechaFin) {
                $fInicio = Carbon::parse($fechaInicio)->toDateString();
                $fFin    = Carbon::parse($fechaFin)->toDateString();
                $enviosQuery->whereBetween('fecha', [$fInicio, $fFin]);
            }

            $envios = $enviosQuery->get()->map(function ($item) {
                return [
                    'id'              => $item->id,
                    'fecha'           => $item->fecha,
                    'monto'           => (float) $item->monto,
                    'saldo_acumulado' => (float) $item->saldo_acumulado,
                    'observacion'     => $item->observacion ?: 'Pago de envío',
                    'cliente'         => $item->cliente ? ['id' => $item->cliente->id, 'nombre' => $item->cliente->nombre, 'dni' => $item->cliente->dni] : null,
                    'tipo_origen'     => 'envios',
                    'metodo_pago'     => $item->metodo_pago,
                    'timestamp'       => Carbon::parse($item->fecha)->timestamp,
                ];
            });

            $consolidado = $consolidado->concat($envios);
        }

        // 2. Obtener Alquileres Cobrados (si no se filtró exclusivamente por envíos)
        if ($servicio === '' || $servicio === 'alquileres') {
            $alquileresQuery = Alquiler::with(['cliente'])->where('cuenta', 'Cobrado');

            if ($clienteId) {
                $alquileresQuery->where('cliente_id', (int) $clienteId);
            }

            if ($buscar !== '') {
                $alquileresQuery->whereHas('cliente', function ($q) use ($buscar) {
                    $q->where('nombre', 'like', "%{$buscar}%")->orWhere('dni', 'like', "%{$buscar}%");
                });
            }

            if ($fechaInicio && $fechaFin) {
                $fInicio = Carbon::parse($fechaInicio)->toDateString();
                $fFin    = Carbon::parse($fechaFin)->toDateString();
                $alquileresQuery->whereBetween('dia', [$fInicio, $fFin]);
            }

            $alquileres = $alquileresQuery->get()->map(function ($item) {
                return [
                    'id'              => $item->id,
                    'fecha'           => $item->dia,
                    'monto'           => (float) $item->importe_proyecto,
                    'saldo_acumulado' => 0.00,
                    'observacion'     => $item->observacion ?: 'Alquiler pagado al contado',
                    'cliente'         => $item->cliente ? ['id' => $item->cliente->id, 'nombre' => $item->cliente->nombre, 'dni' => $item->cliente->dni] : null,
                    'tipo_origen'     => 'alquileres',
                    'metodo_pago'     => $item->destino_pago,
                    'timestamp'       => Carbon::parse($item->dia)->timestamp,
                ];
            });

            $consolidado = $consolidado->concat($alquileres);
        }

        // Fusionar y Ordenar Descendente
        $consolidado = $consolidado->sortByDesc('timestamp')->values();

        // Calcular KPIs Globales
        $totalRecaudado   = $consolidado->sum('monto');
        $abonosRealizados = $consolidado->count();
        $clientesAbonaron = $consolidado->pluck('cliente.id')->filter()->unique()->count();

        // Paginación Manual
        $perPage = 15;
        $page = (int) $request->query('page', 1);
        $paginatedItems = $consolidado->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'ok'   => true,
            'kpis' => [
                'total_recaudado'   => round($totalRecaudado, 2),
                'abonos_realizados' => $abonosRealizados,
                'clientes_abonaron' => $clientesAbonaron,
            ],
            'data' => [
                'current_page' => $page,
                'data'         => $paginatedItems,
                'last_page'    => max(1, (int) ceil($consolidado->count() / $perPage)),
                'per_page'     => $perPage,
                'total'        => $consolidado->count(),
            ]
        ]);
    }

    public function autocompletarClientes(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['ok' => true, 'data' => []]);
        }

        $clientes = Cliente::query()
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
        $servicio    = trim((string) $request->query('servicio', ''));

        $consolidado = collect();

        if ($servicio === '' || $servicio === 'envios') {
            $enviosQuery = CuentaCorriente::with(['cliente', 'envio'])
                ->where('tipo', 'abono')
                ->whereNotNull('envio_id');

            if ($clienteId) {
                $enviosQuery->where('cliente_id', (int) $clienteId);
            }
            if ($buscar !== '') {
                $enviosQuery->whereHas('cliente', function ($q) use ($buscar) {
                    $q->where('nombre', 'like', "%{$buscar}%")->orWhere('dni', 'like', "%{$buscar}%");
                });
            }
            if ($fechaInicio && $fechaFin) {
                $fInicio = Carbon::parse($fechaInicio)->toDateString();
                $fFin    = Carbon::parse($fechaFin)->toDateString();
                $enviosQuery->whereBetween('fecha', [$fInicio, $fFin]);
            }

            $envios = $enviosQuery->get()->map(function ($item) {
                return [
                    'id'          => $item->id,
                    'fecha'       => $item->fecha,
                    'cliente'     => $item->cliente ? $item->cliente->nombre : 'Sin cliente',
                    'dni'         => $item->cliente ? $item->cliente->dni : '-',
                    'telefono'    => $item->cliente ? $item->cliente->telefono : '-',
                    'referencia'  => $item->envio ? ($item->envio->guia ?? $item->envio->codigo) : '-',
                    'tipo_origen' => 'Envío',
                    'metodo_pago' => $item->metodo_pago ?? 'Efectivo',
                    'monto'       => (float) $item->monto,
                    'timestamp'   => Carbon::parse($item->fecha)->timestamp,
                ];
            });
            $consolidado = $consolidado->concat($envios);
        }

        if ($servicio === '' || $servicio === 'alquileres') {
            $alquileresQuery = Alquiler::with(['cliente'])->where('cuenta', 'Cobrado');

            if ($clienteId) {
                $alquileresQuery->where('cliente_id', (int) $clienteId);
            }
            if ($buscar !== '') {
                $alquileresQuery->whereHas('cliente', function ($q) use ($buscar) {
                    $q->where('nombre', 'like', "%{$buscar}%")->orWhere('dni', 'like', "%{$buscar}%");
                });
            }
            if ($fechaInicio && $fechaFin) {
                $fInicio = Carbon::parse($fechaInicio)->toDateString();
                $fFin    = Carbon::parse($fechaFin)->toDateString();
                $alquileresQuery->whereBetween('dia', [$fInicio, $fFin]);
            }

            $alquileres = $alquileresQuery->get()->map(function ($item) {
                return [
                    'id'          => $item->id,
                    'fecha'       => $item->dia,
                    'cliente'     => $item->cliente ? $item->cliente->nombre : 'Sin cliente',
                    'dni'         => $item->cliente ? $item->cliente->dni : '-',
                    'telefono'    => $item->cliente ? $item->cliente->telefono : '-',
                    'referencia'  => 'Alquiler Contado',
                    'tipo_origen' => 'Alquiler',
                    'metodo_pago' => $item->destino_pago ?? 'Efectivo',
                    'monto'       => (float) $item->importe_proyecto,
                    'timestamp'   => Carbon::parse($item->dia)->timestamp,
                ];
            });
            $consolidado = $consolidado->concat($alquileres);
        }

        $consolidado = $consolidado->sortByDesc('timestamp')->values();

        $headers = [
            'ID',
            'Fecha',
            'Cliente',
            'DNI / Documento',
            'Teléfono',
            'Referencia (Guía / Envío)',
            'Tipo Movimiento',
            'Método Pago',
            'Monto Recaudado',
        ];

        $rows = $consolidado->map(function ($mov) {
            return [
                $mov['id'],
                $mov['fecha'] ? Carbon::parse($mov['fecha'])->format('d/m/Y') : '-',
                $mov['cliente'],
                $mov['dni'],
                $mov['telefono'],
                $mov['referencia'],
                $mov['tipo_origen'],
                $mov['metodo_pago'],
                number_format($mov['monto'], 2, '.', ''),
            ];
        });

        $content = $this->xlsx($headers, $rows->all(), 'AuditoriaCobros', 'TablaCobros');
        $filename = 'auditoria_cobros_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => (string) strlen($content),
        ]);
    }
}