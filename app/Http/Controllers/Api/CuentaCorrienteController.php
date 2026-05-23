<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        $clienteDni = trim((string) $request->query('cliente_dni', ''));
        $tipo = trim((string) $request->query('tipo', ''));

        $query = CuentaCorriente::query()
            ->with(['cliente', 'envio'])
            ->when($clienteDni !== '', fn ($q) => $q->where('cliente_dni', $clienteDni))
            ->when($tipo !== '', fn ($q) => $q->where('tipo', $tipo))
            ->latest('id');

        $movimientos = $query->get()->map(fn (CuentaCorriente $cc) => $this->format($cc));

        return response()->json([
            'ok' => true,
            'data' => $movimientos,
        ]);
    }

    public function saldoCliente(string $dni): JsonResponse
    {
        $cliente = Cliente::query()->where('dni', $dni)->first();

        if (! $cliente) {
            return response()->json(['ok' => false, 'message' => 'Cliente no encontrado'], 404);
        }

        $cargos = CuentaCorriente::query()
            ->where('cliente_dni', $dni)
            ->where('tipo', 'cargo')
            ->sum('monto');

        $abonos = CuentaCorriente::query()
            ->where('cliente_dni', $dni)
            ->where('tipo', 'abono')
            ->sum('monto');

        $enviosConDeuda = Envio::query()
            ->where('cliente_dni', $dni)
            ->where('pago', 'Credito')
            ->whereNotNull('monto')
            ->sum('monto');

        $ultimoSaldo = CuentaCorriente::query()
            ->where('cliente_dni', $dni)
            ->latest('id')
            ->value('saldo_acumulado');

        return response()->json([
            'ok' => true,
            'data' => [
                'cliente_dni' => $dni,
                'cliente_nombre' => $cliente->nombre,
                'total_cargos' => round((float) $cargos, 2),
                'total_abonos' => round((float) $abonos, 2),
                'saldo_pendiente' => round(((float) $cargos - (float) $abonos), 2),
                'envios_credito' => round((float) $enviosConDeuda, 2),
                'ultimo_saldo_registrado' => $ultimoSaldo ? round((float) $ultimoSaldo, 2) : 0,
            ],
        ]);
    }

    public function clientesConDeuda(): JsonResponse
    {
        $clientes = Cliente::query()
            ->select('dni', 'nombre', 'telefono')
            ->get()
            ->map(function (Cliente $cliente): array {
                $cargos = CuentaCorriente::query()
                    ->where('cliente_dni', $cliente->dni)
                    ->where('tipo', 'cargo')
                    ->sum('monto');

                $abonos = CuentaCorriente::query()
                    ->where('cliente_dni', $cliente->dni)
                    ->where('tipo', 'abono')
                    ->sum('monto');

                $enviosCredito = Envio::query()
                    ->where('cliente_dni', $cliente->dni)
                    ->where('pago', 'Credito')
                    ->whereNotNull('monto')
                    ->sum('monto');

                $saldo = round(((float) $cargos + (float) $enviosCredito - (float) $abonos), 2);

                return [
                    'dni' => $cliente->dni,
                    'nombre' => $cliente->nombre,
                    'telefono' => $cliente->telefono,
                    'saldo_pendiente' => $saldo,
                    'tiene_deuda' => $saldo > 0,
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
            'cliente_dni' => ['required', 'string', 'exists:clientes,dni'],
            'monto' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'fecha' => ['required', 'date'],
            'observacion' => ['nullable', 'string', 'max:255'],
        ]);

        $abono = DB::transaction(function () use ($validated): CuentaCorriente {
            $ultimoSaldo = CuentaCorriente::query()
                ->where('cliente_dni', $validated['cliente_dni'])
                ->latest('id')
                ->value('saldo_acumulado');

            $saldoActual = $ultimoSaldo !== null ? (float) $ultimoSaldo : $this->calcularSaldoCliente($validated['cliente_dni']);
            $nuevoSaldo = round($saldoActual - (float) $validated['monto'], 2);

            return CuentaCorriente::create([
                'cliente_dni' => $validated['cliente_dni'],
                'tipo' => 'abono',
                'monto' => round((float) $validated['monto'], 2),
                'saldo_acumulado' => $nuevoSaldo,
                'fecha' => $validated['fecha'],
                'observacion' => trim((string) ($validated['observacion'] ?? '')) ?: null,
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
            $ultimoSaldo = CuentaCorriente::query()
                ->where('cliente_dni', $envio->cliente_dni)
                ->latest('id')
                ->value('saldo_acumulado');

            $saldoActual = $ultimoSaldo !== null ? (float) $ultimoSaldo : $this->calcularSaldoCliente($envio->cliente_dni);
            $nuevoSaldo = round($saldoActual + (float) $envio->monto, 2);

            return CuentaCorriente::create([
                'cliente_dni' => $envio->cliente_dni,
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

    private function calcularSaldoCliente(string $dni): float
    {
        $cargos = CuentaCorriente::query()
            ->where('cliente_dni', $dni)
            ->where('tipo', 'cargo')
            ->sum('monto');

        $abonos = CuentaCorriente::query()
            ->where('cliente_dni', $dni)
            ->where('tipo', 'abono')
            ->sum('monto');

        return round(((float) $cargos - (float) $abonos), 2);
    }

    private function format(CuentaCorriente $cc): array
    {
        return [
            'id' => $cc->id,
            'cliente_dni' => $cc->cliente_dni,
            'cliente_nombre' => $cc->cliente?->nombre,
            'envio_id' => $cc->envio_id,
            'envio_codigo' => $cc->envio?->codigo,
            'tipo' => $cc->tipo,
            'monto' => $cc->monto,
            'saldo_acumulado' => $cc->saldo_acumulado,
            'fecha' => $cc->fecha?->format('Y-m-d'),
            'observacion' => $cc->observacion,
            'created_at' => $cc->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
