<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gasto;
use App\Traits\GeneratesExcel;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class GastoController extends Controller
{
    use GeneratesExcel;

    public function index(Request $request): JsonResponse
    {
        $fechaInicio = $request->query('fecha_inicio');
        $fechaFin    = $request->query('fecha_fin');
        $tipo        = trim((string) $request->query('tipo', ''));
        $buscar      = trim((string) $request->query('buscar', ''));

        $query = Gasto::query();

        if ($tipo !== '') {
            $query->where('tipo', $tipo);
        }

        if ($buscar !== '') {
            $query->where(function ($q) use ($buscar): void {
                $q->where('destino', 'like', "%{$buscar}%")
                  ->orWhere('detalle', 'like', "%{$buscar}%");
            });
        }

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('fecha', [
                Carbon::parse($fechaInicio)->toDateString(),
                Carbon::parse($fechaFin)->toDateString(),
            ]);
        }

        // KPIs
        $kpiQuery = (clone $query);
        $totalGastos       = (float) $kpiQuery->sum('monto');
        $cantidadRegistros = (int) $kpiQuery->count();
        $promedioGasto     = $cantidadRegistros > 0 ? round($totalGastos / $cantidadRegistros, 2) : 0.0;

        $porTipo = (clone $query)
            ->selectRaw('tipo, COUNT(id) as total_items, COALESCE(SUM(monto), 0) as total_monto')
            ->groupBy('tipo')
            ->orderByDesc('total_monto')
            ->get();

        $gastos = $query->with('user:id,name')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(15);

        return response()->json([
            'ok'   => true,
            'kpis' => [
                'total_gastos'       => round($totalGastos, 2),
                'cantidad_registros' => $cantidadRegistros,
                'promedio_gasto'     => round($promedioGasto, 2),
                'por_tipo'           => $porTipo,
            ],
            'data' => $gastos,
        ]);
    }

    public function buscarDestinos(Request $request): JsonResponse
    {
        $q = trim((string) ($request->query('q') ?? $request->query('buscar', '')));

        $query = Gasto::query()
            ->select('destino')
            ->whereNotNull('destino')
            ->where('destino', '!=', '');

        if ($q !== '') {
            $query->where('destino', 'like', "%{$q}%")
                ->groupBy('destino')
                ->orderByRaw("CASE 
                    WHEN destino = ? THEN 1 
                    WHEN destino LIKE ? THEN 2 
                    ELSE 3 
                END", [$q, "{$q}%"])
                ->orderBy('destino');
        } else {
            $query->groupBy('destino')
                ->orderByRaw('COUNT(id) DESC');
        }

        $destinos = $query->limit(3)->pluck('destino');

        return response()->json([
            'ok'   => true,
            'data' => $destinos,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fecha'   => ['required', 'date'],
            'tipo'    => ['required', 'string', Rule::in(Gasto::TIPOS)],
            'monto'   => ['required', 'numeric', 'min:0.01'],
            'destino' => ['required', 'string', 'max:150'],
            'detalle' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['user_id'] = Auth::id();

        $gasto = Gasto::create($validated);
        $gasto->load('user:id,name');

        return response()->json([
            'ok'      => true,
            'message' => 'Gasto registrado correctamente.',
            'data'    => $gasto,
        ], 201);
    }

    public function show(Gasto $gasto): JsonResponse
    {
        $gasto->load('user:id,name');

        return response()->json([
            'ok'   => true,
            'data' => $gasto,
        ]);
    }

    public function update(Request $request, Gasto $gasto): JsonResponse
    {
        $validated = $request->validate([
            'fecha'   => ['required', 'date'],
            'tipo'    => ['required', 'string', Rule::in(Gasto::TIPOS)],
            'monto'   => ['required', 'numeric', 'min:0.01'],
            'destino' => ['required', 'string', 'max:150'],
            'detalle' => ['nullable', 'string', 'max:1000'],
        ]);

        $gasto->update($validated);
        $gasto->load('user:id,name');

        return response()->json([
            'ok'      => true,
            'message' => 'Gasto actualizado correctamente.',
            'data'    => $gasto,
        ]);
    }

    public function destroy(Gasto $gasto): JsonResponse
    {
        $gasto->delete();

        return response()->json([
            'ok'      => true,
            'message' => 'Gasto eliminado correctamente.',
        ]);
    }

    public function export(Request $request): Response
    {
        $fechaInicio = $request->query('fecha_inicio');
        $fechaFin    = $request->query('fecha_fin');
        $tipo        = trim((string) $request->query('tipo', ''));
        $buscar      = trim((string) $request->query('buscar', ''));

        $query = Gasto::query()->with('user:id,name');

        if ($tipo !== '') {
            $query->where('tipo', $tipo);
        }

        if ($buscar !== '') {
            $query->where(function ($q) use ($buscar): void {
                $q->where('destino', 'like', "%{$buscar}%")
                  ->orWhere('detalle', 'like', "%{$buscar}%");
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
            'Tipo',
            'Destino',
            'Monto (S/)',
            'Detalle',
            'Registrado Por',
            'Fecha Registro',
        ];

        $gastos = $query->orderByDesc('fecha')->orderByDesc('id')->get();

        $rows = $gastos->map(function (Gasto $gasto): array {
            return [
                $gasto->id,
                $gasto->fecha ? Carbon::parse($gasto->fecha)->format('d/m/Y') : '-',
                $gasto->tipo,
                $gasto->destino,
                number_format((float) $gasto->monto, 2, '.', ''),
                $gasto->detalle ?: '-',
                $gasto->user?->name ?? 'Sistema',
                $gasto->created_at ? $gasto->created_at->format('d/m/Y H:i') : '-',
            ];
        });

        $content = $this->xlsx($headers, $rows->all(), 'GastosOperativos', 'TablaGastos');
        $filename = 'auditoria_gastos_'.now()->format('Y-m-d_H-i-s').'.xlsx';

        return response($content, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length'      => (string) strlen($content),
        ]);
    }
}
