<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Envio;
use App\Models\EnvioItem;
use App\Models\Alquiler;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function ganancias(Request $request): JsonResponse
    {
        $request->validate([
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
            'preset'      => ['nullable', 'string', 'in:semana_actual,semana_anterior,mes_actual,personalizado'],
        ]);

        $preset = $request->query('preset', 'semana_actual');

        if ($preset === 'semana_anterior') {
            $desde = Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY)->toDateString();
            $hasta = Carbon::now()->subWeek()->endOfWeek(Carbon::SUNDAY)->toDateString();
        } elseif ($preset === 'mes_actual') {
            $desde = Carbon::now()->startOfMonth()->toDateString();
            $hasta = Carbon::now()->endOfMonth()->toDateString();
        } elseif ($preset === 'personalizado') {
            $desde = $request->query('fecha_desde', Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString());
            $hasta = $request->query('fecha_hasta', Carbon::now()->endOfWeek(Carbon::SUNDAY)->toDateString());
        } else {
            $desde = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();
            $hasta = Carbon::now()->endOfWeek(Carbon::SUNDAY)->toDateString();
        }

        // 1. Totales y KPIs del período (Envíos)
        $metricas = Envio::query()
            ->whereBetween('fecha', [$desde, $hasta])
            ->selectRaw('
                COUNT(id) as total_envios,
                COALESCE(SUM(monto), 0) as total_ingresos,
                COALESCE(SUM(costo_transportista), 0) as total_fletes
            ')
            ->first();
            
        // 1.1 Totales y KPIs de Alquileres (Solo Cobrados)
        $metricasAlquiler = Alquiler::query()
            ->whereBetween('dia', [$desde, $hasta])
            ->where('cuenta', 'Cobrado')
            ->selectRaw('
                COUNT(id) as total_alquileres,
                COALESCE(SUM(importe_proyecto), 0) as total_ingresos
            ')
            ->first();

        // 1.2 Suma Maestra
        $ingresos = (float) ($metricas->total_ingresos ?? 0) + (float) ($metricasAlquiler->total_ingresos ?? 0);
        $fletes = (float) ($metricas->total_fletes ?? 0);
        $utilidad = $ingresos - $fletes;
        $margenPct = $ingresos > 0 ? round(($utilidad / $ingresos) * 100, 1) : 0.0;
        
        $totalOperaciones = (int) ($metricas->total_envios ?? 0) + (int) ($metricasAlquiler->total_alquileres ?? 0);

        // 2. Serie diaria (Envíos)
        $serieDiaria = Envio::query()
            ->whereBetween('fecha', [$desde, $hasta])
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->selectRaw('
                fecha,
                COALESCE(SUM(monto), 0) as ingreso,
                COALESCE(SUM(costo_transportista), 0) as flete
            ')
            ->get()
            ->keyBy(fn($row) => Carbon::parse($row->fecha)->toDateString());
            
        // 2.1 Serie diaria (Alquileres Cobrados)
        $serieDiariaAlquiler = Alquiler::query()
            ->whereBetween('dia', [$desde, $hasta])
            ->where('cuenta', 'Cobrado')
            ->groupBy('dia')
            ->selectRaw('
                dia as fecha,
                COALESCE(SUM(importe_proyecto), 0) as ingreso
            ')
            ->get()
            ->keyBy(fn($row) => Carbon::parse($row->fecha)->toDateString());

        $chartLabels = [];
        $chartIngresos = [];
        $chartFletes = [];
        $chartGanancia = [];

        foreach (CarbonPeriod::create($desde, $hasta) as $dia) {
            $fStr = $dia->toDateString();
            
            $item = $serieDiaria->get($fStr);
            $itemAlq = $serieDiariaAlquiler->get($fStr);
            
            $chartLabels[] = ucfirst($dia->isoFormat('ddd D'));
            
            // Sumamos los ingresos de envíos y alquileres para el mismo día
            $ing = ((float) ($item->ingreso ?? 0)) + ((float) ($itemAlq->ingreso ?? 0));
            $fle = (float) ($item->flete ?? 0);
            
            $chartIngresos[] = round($ing, 2);
            $chartFletes[] = round($fle, 2);
            $chartGanancia[] = round($ing - $fle, 2);
        }

        // 3. Top 5 Clientes con mayor deuda 
        $topClientesDeudores = Envio::query()
            ->join('clientes', 'envios.cliente_id', '=', 'clientes.id')
            ->where(function ($q) {
                $q->where('envios.pago', '!=', 'pagado')
                  ->orWhereNull('envios.pago');
            })
            ->groupBy('clientes.id', 'clientes.nombre')
            ->orderByDesc('deuda_total')
            ->limit(5)
            ->selectRaw('
                clientes.id,
                clientes.nombre as cliente,
                COUNT(envios.id) as envios_pendientes,
                COALESCE(SUM(envios.monto), 0) as deuda_total
            ')
            ->get();

        // 4. Top 5 Transportistas a quienes más se les debe
        $topTransportistasDeuda = EnvioItem::query()
            ->join('envios', 'envio_items.envio_id', '=', 'envios.id')
            ->join('transportistas', 'envio_items.transportista_id', '=', 'transportistas.id')
            ->whereNull('envios.pagado_transportista_at')
            ->groupBy('transportistas.id', 'transportistas.nombre')
            ->orderByDesc('total_pendiente')
            ->limit(5)
            ->selectRaw('
                transportistas.id,
                transportistas.nombre as transportista,
                COUNT(DISTINCT envios.id) as viajes_pendientes,
                COALESCE(SUM(envio_items.costo_transportista), 0) as total_pendiente
            ')
            ->having('total_pendiente', '>', 0)
            ->get();

        return response()->json([
            'periodo' => [
                'desde'  => $desde,
                'hasta'  => $hasta,
                'preset' => $preset,
            ],
            'kpis' => [
                'ingresos'     => round($ingresos, 2),
                'fletes'       => round($fletes, 2),
                'utilidad'     => round($utilidad, 2),
                'margen_pct'   => $margenPct,
                'total_envios' => $totalOperaciones, // Ahora también cuenta los alquileres en el contador visual
            ],
            'chart' => [
                'labels'   => $chartLabels,
                'ingresos' => $chartIngresos,
                'fletes'   => $chartFletes,
                'ganancia' => $chartGanancia,
            ],
            'top_deudores'       => $topClientesDeudores,
            'top_transportistas' => $topTransportistasDeuda,
        ]);
    }
}