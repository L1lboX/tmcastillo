<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alquiler;
use App\Models\Cliente;
use App\Models\CuentaCorriente;
use App\Models\Envio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CuentaCorrienteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $clienteId = trim((string) $request->query('cliente_id', ''));
        $tipo = trim((string) $request->query('tipo', ''));

        $query = CuentaCorriente::query()
            ->with(['cliente', 'envio', 'alquiler.maquinaria'])
            ->when($tipo !== '', fn ($q) => $q->where('tipo', $tipo));

        if ($clienteId !== '') {
            $clienteIdInt = (int) $clienteId;
            $query->where('cliente_id', $clienteIdInt);

            $ultimoCorteId = CuentaCorriente::query()
                ->where('cliente_id', $clienteIdInt)
                ->where('saldo_acumulado', '<=', 0.000)
                ->max('id');

            if ($ultimoCorteId) {
                $query->where('id', '>', $ultimoCorteId);
            }
        }

        $movimientos = $query->orderBy('id', 'asc')->get()->map(fn (CuentaCorriente $cc) => $this->format($cc));

        return response()->json([
            'ok' => true,
            'data' => $movimientos,
        ]);
    }

    public function saldoCliente(Cliente $cliente): JsonResponse
    {
        $cargos = CuentaCorriente::query()
            ->where('cliente_id', $cliente->id)
            ->where('tipo', 'cargo')
            ->sum('monto');

        $abonos = CuentaCorriente::query()
            ->where('cliente_id', $cliente->id)
            ->where('tipo', 'abono')
            ->sum('monto');

        $saldoPendiente = round(((float) $cargos - (float) $abonos), 2);

        return response()->json([
            'ok' => true,
            'data' => [
                'cliente_id' => $cliente->id,
                'cliente_dni' => $cliente->dni,
                'cliente_nombre' => $cliente->nombre,
                'total_cargos' => round((float) $cargos, 3),
                'total_abonos' => round((float) $abonos, 3),
                'saldo_pendiente' => $saldoPendiente,
            ],
        ]);
    }

    public function clientesConDeuda(): JsonResponse
    {
        $clientes = Cliente::query()
            ->select('id', 'dni', 'nombre', 'telefono')
            ->get()
            ->map(function (Cliente $cliente): array {
                $cargos = CuentaCorriente::query()
                    ->where('cliente_id', $cliente->id)
                    ->where('tipo', 'cargo')
                    ->sum('monto');

                $abonos = CuentaCorriente::query()
                    ->where('cliente_id', $cliente->id)
                    ->where('tipo', 'abono')
                    ->sum('monto');

                $saldo = round(((float) $cargos - (float) $abonos), 3);

                // Desglose de deudas brutas iniciales
                $deudaEnviosBruta = Envio::query()
                    ->where('cliente_id', $cliente->id)
                    ->where('pago', 'Credito')
                    ->sum('monto');

                $deudaAlquileresBruta = Alquiler::query()
                    ->where('cliente_id', $cliente->id)
                    ->where('cuenta', 'Pendiente')
                    ->sum('importe_proyecto');

                // Si el saldo total del cliente es 0 o menor, ambas deudas bajadas a 0 obligatoriamente
                if ($saldo <= 0.000) {
                    $deudaEnvios = 0.00;
                    $deudaAlquileres = 0.00;
                } else {
                    // Si hay abonos parciales, distribuimos el saldo pendiente de forma proporcional 
                    // o priorizando los alquileres pendientes actuales para que la tarjeta baje dinámicamente.
                    $deudaAlquileres = (float) Alquiler::query()
                        ->where('cliente_id', $cliente->id)
                        ->where('cuenta', 'Pendiente')
                        ->sum('importe_proyecto');
                    
                    // Aseguramos que la deuda de alquileres nunca supere el saldo global real del cliente
                    $deudaAlquileres = min($deudaAlquileres, $saldo);
                    
                    // El resto del saldo pertenecerá a envíos (si aplica)
                    $deudaEnvios = max(0, round($saldo - $deudaAlquileres, 2));
                }

                return [
                    'id' => $cliente->id,
                    'dni' => $cliente->dni,
                    'nombre' => $cliente->nombre,
                    'telefono' => $cliente->telefono,
                    'saldo_pendiente' => $saldo,
                    'deuda_envios' => round((float) $deudaEnvios, 2),
                    'deuda_alquileres' => round((float) $deudaAlquileres, 2),
                    'tiene_deuda' => $saldo > 0.000,
                ];
            })
            ->filter(fn (array $c) => $c['tiene_deuda'])
            ->sortByDesc('saldo_pendiente')
            ->values();

        return response()->json([
            'ok' => true,
            'data' => $clientes,
        ]);
    }

    public function registrarAbono(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id'  => ['required', 'integer', 'exists:clientes,id'],
            'monto'       => ['required', 'numeric', 'min:0.001', 'max:99999999.999'],
            'fecha'       => ['required', 'date'],
            'origen'      => ['nullable', 'string', 'in:envios,alquileres'], 
            'metodo_pago' => ['required', 'string', 'max:50'],               
            'observacion' => ['nullable', 'string', 'max:255'],
        ]);

        $abono = DB::transaction(function () use ($validated): CuentaCorriente {
            $clienteId  = (int) $validated['cliente_id'];
            $montoAbono = round((float) $validated['monto'], 3);
            $origen     = $validated['origen'] ?? 'envios';
            $metodoPago = $validated['metodo_pago'];

            // Lógica FIFO de liquidación (liquida deudas de la más antigua a la más nueva)
            if ($origen === 'alquileres') {
                $alquileresPendientes = Alquiler::query()
                    ->where('cliente_id', $clienteId)
                    ->where('cuenta', 'Pendiente')
                    ->where('importe_proyecto', '>', 0)
                    ->orderBy('dia', 'asc')
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get();

                $saldoParaCubrir = $montoAbono;

                foreach ($alquileresPendientes as $alquiler) {
                    $montoTotalAlquiler = (float) $alquiler->importe_proyecto;

                    // Si el abono actual cubre el alquiler completo
                    if ($saldoParaCubrir >= $montoTotalAlquiler) {
                        $alquiler->cuenta = 'Cobrado';
                        $alquiler->destino_pago = $metodoPago; 
                        $alquiler->save();

                        $saldoParaCubrir = round($saldoParaCubrir - $montoTotalAlquiler, 3);
                    } else {
                        // Si fue un pago parcial, revisamos si con este abono 
                        // la deuda global del cliente quedó en 0 (o menor a 5 céntimos por decimales)
                        // Calculamos el saldo temporal restando el abono que acabamos de meter
                        $cargosTotales = CuentaCorriente::where('cliente_id', $clienteId)->where('tipo', 'cargo')->sum('monto');
                        $abonosTotales = CuentaCorriente::where('cliente_id', $clienteId)->where('tipo', 'abono')->sum('monto') + $montoAbono;
                        $saldoGlobalRestante = round($cargosTotales - $abonosTotales, 3);

                        // Si ya pagó todo lo que debía en total, liquidamos este alquiler pendiente de una vez
                        if ($saldoGlobalRestante <= 0.05) {
                            $alquiler->cuenta = 'Cobrado';
                            $alquiler->destino_pago = $metodoPago; 
                            $alquiler->save();
                        }

                        $saldoParaCubrir = 0;
                    }

                    if ($saldoParaCubrir <= 0) {
                        break;
                    }
                }
            }else {
                $enviosPendientes = Envio::query()
                    ->where('cliente_id', $clienteId)
                    ->where('pago', 'Credito')
                    ->whereNotNull('monto')
                    ->where('monto', '>', 0)
                    ->orderBy('fecha', 'asc')
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get();

                $saldoParaCubrir = $montoAbono;

                foreach ($enviosPendientes as $envio) {
                    $montoEnvio = (float) $envio->monto;

                    if ($saldoParaCubrir >= $montoEnvio) {
                        $envio->pago = 'Pagado';
                        $envio->save();

                        $saldoParaCubrir = round($saldoParaCubrir - $montoEnvio, 3);
                    } else {
                        $saldoParaCubrir = 0;
                    }

                    if ($saldoParaCubrir <= 0) {
                        break;
                    }
                }
            }

            $saldoActual = $this->calcularSaldoCliente($clienteId);
            $nuevoSaldo  = round($saldoActual - $montoAbono, 3);

            $detalleObservacion = "Abono [".strtoupper($origen)."] vía {$metodoPago}";
            if (!empty($validated['observacion'])) {
                $detalleObservacion .= " - ".$validated['observacion'];
            }

            return CuentaCorriente::create([
                'cliente_id'      => $clienteId,
                'tipo'            => 'abono',
                'metodo_pago'     => $metodoPago, // Guardar el método de pago explícitamente
                'monto'           => $montoAbono,
                'saldo_acumulado' => $nuevoSaldo,
                'fecha'           => $validated['fecha'],
                'observacion'     => $detalleObservacion,
            ]);
        });

        return response()->json([
            'ok' => true,
            'data' => $this->format($abono->load(['cliente'])),
        ], 201);
    }

    public function syncEnvioCredito(Envio $envio): JsonResponse
    {
        if ($envio->pago !== 'Credito' || ! $envio->monto) {
            return response()->json([
                'ok' => false,
                'message' => 'El envio no tiene credito registrado',
            ], 422);
        }

        $existe = CuentaCorriente::query()
            ->where('envio_id', $envio->id)
            ->where('tipo', 'cargo')
            ->exists();

        if ($existe) {
            return response()->json([
                'ok' => true,
                'message' => 'El cargo ya fue registrado',
                'data' => null,
            ]);
        }

        $cargo = DB::transaction(function () use ($envio): CuentaCorriente {
            $saldoActual = $this->calcularSaldoCliente($envio->cliente_id);
            $nuevoSaldo = round($saldoActual + (float) $envio->monto, 2);

            return CuentaCorriente::create([
                'cliente_id' => $envio->cliente_id,
                'envio_id' => $envio->id,
                'tipo' => 'cargo',
                'monto' => (float) $envio->monto,
                'saldo_acumulado' => $nuevoSaldo,
                'fecha' => $envio->fecha,
                'observacion' => 'Cargo automatico por envio '.$envio->codigo.' (Credito)',
            ]);
        });

        return response()->json([
            'ok' => true,
            'data' => $this->format($cargo->load(['cliente', 'envio'])),
        ], 201);
    }

    public function syncAlquilerCredito(Alquiler $alquiler): JsonResponse
    {
        $cargoExistente = CuentaCorriente::query()
            ->where('alquiler_id', $alquiler->id)
            ->where('tipo', 'cargo')
            ->first();

        if ($alquiler->cuenta === 'Cobrado') {
            if (! $cargoExistente) {
                return response()->json([
                    'ok' => true,
                    'message' => 'Alquiler cobrado al contado, no genera deuda.',
                ]);
            }

            DB::transaction(function () use ($cargoExistente, $alquiler): void {
                $cargoExistente->delete();
                $this->recalcularSaldosCliente($alquiler->cliente_id);
            });

            return response()->json([
                'ok' => true,
                'message' => 'Cargo de alquiler retirado al marcarse como Cobrado.',
            ]);
        }

        if ($alquiler->cuenta === 'Pendiente') {
            $montoAlquiler = round((float) $alquiler->importe_proyecto, 3);

            if ($montoAlquiler <= 0) {
                return response()->json([
                    'ok' => false,
                    'message' => 'El importe debe ser mayor a 0 para generar cargo.',
                ], 422);
            }

            $cargo = DB::transaction(function () use ($cargoExistente, $alquiler, $montoAlquiler): CuentaCorriente {
                $placa = $alquiler->maquinaria?->placa ?? 'Maquinaria';
                $descripcion = "Cargo por alquiler {$placa}".($alquiler->proyecto ? " - Obra: {$alquiler->proyecto}" : '');

                if ($cargoExistente) {
                    $cargoExistente->update([
                        'monto'       => $montoAlquiler,
                        'fecha'       => $alquiler->dia,
                        'observacion' => $descripcion,
                    ]);

                    $this->recalcularSaldosCliente($alquiler->cliente_id);

                    return $cargoExistente->fresh();
                }

                $saldoActual = $this->calcularSaldoCliente($alquiler->cliente_id);
                $nuevoSaldo  = round($saldoActual + $montoAlquiler, 3);

                return CuentaCorriente::create([
                    'cliente_id'      => $alquiler->cliente_id,
                    'alquiler_id'     => $alquiler->id,
                    'tipo'            => 'cargo',
                    'monto'           => $montoAlquiler,
                    'saldo_acumulado' => $nuevoSaldo,
                    'fecha'           => $alquiler->dia,
                    'observacion'     => $descripcion,
                ]);
            });

            return response()->json([
                'ok' => true,
                'data' => $this->format($cargo->load(['cliente', 'alquiler.maquinaria'])),
            ], 201);
        }

        return response()->json(['ok' => true]);
    }

    private function calcularSaldoCliente(int $clienteId): float
    {
        $cargos = CuentaCorriente::query()
            ->where('cliente_id', $clienteId)
            ->where('tipo', 'cargo')
            ->sum('monto');

        $abonos = CuentaCorriente::query()
            ->where('cliente_id', $clienteId)
            ->where('tipo', 'abono')
            ->sum('monto');

        return round(((float) $cargos - (float) $abonos), 3);
    }

    private function recalcularSaldosCliente(int $clienteId): void
    {
        $movimientos = CuentaCorriente::query()
            ->where('cliente_id', $clienteId)
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();

        $saldoCorriendo = 0.000;

        foreach ($movimientos as $mov) {
            if ($mov->tipo === 'cargo') {
                $saldoCorriendo = round($saldoCorriendo + (float) $mov->monto, 3);
            } elseif ($mov->tipo === 'abono') {
                $saldoCorriendo = round($saldoCorriendo - (float) $mov->monto, 3);
            }

            $mov->saldo_acumulado = $saldoCorriendo;
            $mov->save();
        }
    }

    private function format(CuentaCorriente $cc): array
    {
        return [
            'id' => $cc->id,
            'cliente_id' => $cc->cliente_id,
            'cliente_dni' => $cc->cliente?->dni,
            'cliente_nombre' => $cc->cliente?->nombre,
            'envio_id' => $cc->envio_id,
            'envio_codigo' => $cc->envio?->codigo,
            'alquiler_id' => $cc->alquiler_id,
            'alquiler_placa' => $cc->alquiler?->maquinaria?->placa,
            'alquiler_proyecto' => $cc->alquiler?->proyecto,
            'tipo' => $cc->tipo,
            'monto' => $cc->monto,
            'metodo_pago' => $cc->metodo_pago,
            'saldo_acumulado' => $cc->saldo_acumulado,
            'fecha' => $cc->fecha ? (is_string($cc->fecha) ? $cc->fecha : $cc->fecha->format('Y-m-d')) : null,
            'observacion' => $cc->observacion,
            'created_at' => $cc->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}