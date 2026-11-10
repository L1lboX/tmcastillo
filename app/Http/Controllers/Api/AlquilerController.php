<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alquiler;
use App\Models\Maquinaria;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class AlquilerController extends Controller{

    public function index(Request $request): JsonResponse{
        $perPage = min((int) $request->query('per_page', 15), 100);
        
        // Sincronizamos los nombres que envía JS con los que usa PHP
        $cuenta = $request->query('estado_pago') ?? $request->query('cuenta'); 
        $fechaDesde = $request->query('fecha_desde');
        $fechaHasta = $request->query('fecha_hasta');
        $q = trim((string) $request->query('search', $request->query('q', '')));

        $paginator = Alquiler::query()
            ->with(['cliente:id,dni,nombre,telefono', 'maquinaria:id,placa,descripcion'])
            ->when($cuenta, fn ($query) => $query->where('cuenta', $cuenta))
            ->when($fechaDesde, fn ($query) => $query->whereDate('dia', '>=', $fechaDesde))
            ->when($fechaHasta, fn ($query) => $query->whereDate('dia', '<=', $fechaHasta))
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($sub) use ($q): void {
                    $sub->where('proyecto', 'like', "%{$q}%")
                        ->orWhere('comprobante', 'like', "%{$q}%")
                        ->orWhereHas('cliente', function ($qCliente) use ($q) {
                            $qCliente->where('nombre', 'like', "%{$q}%")
                                     ->orWhere('dni', 'like', "%{$q}%");
                        })
                        ->orWhereHas('maquinaria', function ($qMaq) use ($q) {
                            $qMaq->where('placa', 'like', "%{$q}%");
                        });
                });
            })
            ->latest('dia')
            ->latest('id')
            ->paginate($perPage)
            ->through(fn (Alquiler $a) => $this->format($a));

        return response()->json([
            'ok' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
        ]);
    }

    public function show(Alquiler $alquiler): JsonResponse{
        return response()->json([
            'ok' => true,
            'data' => $this->format($alquiler->load(['cliente', 'maquinaria'])),
        ]);
    }


    public function store(Request $request): JsonResponse{
        $validated = $request->validate([
            'dia'           => ['required', 'date'],
            'cliente_id'    => ['required', 'integer', 'exists:clientes,id'],
            'maquinaria_id' => ['required', 'integer', 'exists:maquinarias,id'],
            'horas'         => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
            'precio'        => ['required', 'numeric', 'min:0.001', 'max:999999.999'],
            'proyecto'      => ['nullable', 'string', 'max:150'], // Asegurado como nullable
            'comprobante'   => ['required', 'string', 'max:50'],
            'cuenta'        => ['required', 'string', 'in:Cobrado,Pendiente'],
            'destino_pago'  => ['required', 'string', 'max:50'],
            'observacion'   => ['nullable', 'string'],
        ]);

        $alquiler = DB::transaction(function () use ($validated): Alquiler {
            $horas = round((float) $validated['horas'], 2);
            $precio = round((float) $validated['precio'], 3);
            $importe = round($horas * $precio, 3); // Agregado el asterisco de multiplicación

            $alquiler = Alquiler::create([
                'dia'              => $validated['dia'],
                'cliente_id'       => (int) $validated['cliente_id'],
                'maquinaria_id'    => (int) $validated['maquinaria_id'],
                'horas'            => $horas,
                'precio'           => $precio,
                'importe_proyecto' => $importe,
                'proyecto' => trim((string) ($validated['proyecto'] ?? '')) ?: 'Ninguno', // Manejo seguro de null
                'comprobante'      => trim($validated['comprobante']),
                'cuenta'           => $validated['cuenta'],
                'destino_pago'     => trim($validated['destino_pago']),
                'observacion'      => trim((string) ($validated['observacion'] ?? '')) ?: null,
            ]);

            if ($alquiler->cuenta === 'Pendiente' && $alquiler->importe_proyecto > 0) {
                app(CuentaCorrienteController::class)->syncAlquilerCredito($alquiler);
            }

            return $alquiler;
        });

        return response()->json([
            'ok' => true,
            'message' => 'Alquiler registrado con éxito.',
            'data' => $this->format($alquiler->load(['cliente', 'maquinaria'])),
        ], 201);
    }

    public function update(Request $request, Alquiler $alquiler): JsonResponse{
        $validated = $request->validate([
            'dia'           => ['required', 'date'],
            'cliente_id'    => ['required', 'integer', 'exists:clientes,id'],
            'maquinaria_id' => ['required', 'integer', 'exists:maquinarias,id'],
            'horas'         => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
            'precio'        => ['required', 'numeric', 'min:0.001', 'max:999999.999'],
            'proyecto'      => ['nullable', 'string', 'max:150'], // Corregido a nullable
            'comprobante'   => ['required', 'string', 'max:50'],
            'cuenta'        => ['required', 'string', 'in:Cobrado,Pendiente'],
            'destino_pago'  => ['required', 'string', 'max:50'],
            'observacion'   => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated, $alquiler): void {
            $horas = round((float) $validated['horas'], 2);
            $precio = round((float) $validated['precio'], 3);
            $importe = round($horas * $precio, 3); // Agregado el asterisco de multiplicación

            $alquiler->update([
                'dia'              => $validated['dia'],
                'cliente_id'       => (int) $validated['cliente_id'],
                'maquinaria_id'    => (int) $validated['maquinaria_id'],
                'horas'            => $horas,
                'precio'           => $precio,
                'importe_proyecto' => $importe,
                'proyecto' => trim((string) ($validated['proyecto'] ?? '')) ?: 'Ninguno',
                'comprobante'      => trim($validated['comprobante']),
                'cuenta'           => $validated['cuenta'],
                'destino_pago'     => trim($validated['destino_pago']),
                'observacion'      => trim((string) ($validated['observacion'] ?? '')) ?: null,
            ]);

            if ($alquiler->cuenta === 'Pendiente' && $alquiler->importe_proyecto > 0) {
                app(CuentaCorrienteController::class)->syncAlquilerCredito($alquiler);
            }
        });

        return response()->json([
            'ok'      => true,
            'message' => 'Alquiler actualizado con éxito.',
            'data'    => $this->format($alquiler->refresh()->load(['cliente', 'maquinaria'])),
        ]);
    }

    public function proyectos(Request $request): JsonResponse{
        $clienteId = $request->query('cliente_id');
        $q = trim((string) $request->query('q', ''));

        $proyectos = Alquiler::query()
            ->when($clienteId, fn ($query) => $query->where('cliente_id', $clienteId))
            ->when($q !== '', fn ($query) => $query->where('proyecto', 'ilike', "%{$q}%")) // Actualizado a ilike
            ->select('proyecto')
            ->distinct()
            ->orderBy('proyecto', 'asc')
            ->limit(20)
            ->pluck('proyecto');

        return response()->json([
            'ok' => true,
            'data' => $proyectos,
        ]);
    }

    private function format(Alquiler $a): array{
        return [
            'id'               => $a->id,
            'dia'              => $a->dia?->format('Y-m-d'),
            'cliente_id'       => $a->cliente_id,
            'cliente_nombre'   => $a->cliente?->nombre,
            'cliente_dni'      => $a->cliente?->dni,
            'maquinaria_id'    => $a->maquinaria_id,
            'maquinaria_placa' => $a->maquinaria?->placa,
            'horas'            => (float) $a->horas,
            'precio'           => round((float) $a->precio, 3),
            'importe_proyecto' => round((float) $a->importe_proyecto, 3),
            'proyecto'         => $a->proyecto,
            'comprobante'      => $a->comprobante,
            'cuenta'           => $a->cuenta,
            'destino_pago'     => $a->destino_pago,
            'observacion'      => $a->observacion,
            'created_at'       => $a->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}