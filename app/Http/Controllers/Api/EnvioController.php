<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CuentaCorriente;
use App\Models\Envio;
use App\Models\EnvioItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\PagoTransportista;

class EnvioController extends Controller
{
    private const PAGOS = ['Pendiente', 'Pagado', 'Contra Entrega', 'Credito'];
    private const ESTADOS = ['Registrado', 'En camino', 'Entregado', 'Devuelto'];

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 15), 100);

        $paginator = $this->filteredQuery($request)
            ->latest('id')
            ->paginate($perPage)
            ->through(fn (Envio $envio) => $this->format($envio));

        return response()->json([
            'ok' => true,
            'data' => $paginator->items(),
            'next_code' => $this->nextCode(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    public function show(Envio $envio): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => $this->format($envio->load(['cliente', 'transportista', 'items'])),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $envio = DB::transaction(function () use ($request): Envio {
            $cliente = ClienteController::upsertFromEnvio($request);
            $data = $this->payload($request);
            $data['cliente_id'] = (int) ($request->input('cliente_id') ?: $cliente->id);

            $envio = Envio::query()->create($data);

            foreach ($request->input('items', []) as $item) {
                $envio->items()->create([
                    'cantidad' => (int) $item['cantidad'],
                    'descripcion' => trim($item['descripcion']),
                    'monto' => (float) ($item['monto'] ?? 0),
                    'costo_transportista' => (float) ($item['costo_transportista'] ?? 0),
                    'transportista_id'    => $item['transportista_id'] ?? null,
                ]);
            }

            return $envio;
        });

        return response()->json([
            'ok' => true,
            'data' => $this->format($envio->load(['cliente', 'transportista', 'items'])),
        ], 201);
    }

    public function update(Request $request, Envio $envio): JsonResponse
    {
        DB::transaction(function () use ($request, $envio): void {
            $cliente = ClienteController::upsertFromEnvio($request);
            $data = $this->payload($request, $envio);
            $data['cliente_id'] = (int) ($request->input('cliente_id') ?: $cliente->id);
            $envio->update($data);

            $envio->items()->delete();
            foreach ($request->input('items', []) as $item) {
                $envio->items()->create([
                    'cantidad' => (int) $item['cantidad'],
                    'descripcion' => trim($item['descripcion']),
                    'monto' => (float) ($item['monto'] ?? 0),
                    'costo_transportista' => (float) ($item['costo_transportista'] ?? 0),
                    'transportista_id'    => $item['transportista_id'] ?? null,
                ]);
            }
        });

        return response()->json([
            'ok' => true,
            'data' => $this->format($envio->refresh()->load(['cliente', 'transportista', 'items'])),
        ]);
    }

    public function liquidar(Request $request, Envio $envio): JsonResponse
    {
        $validated = $request->validate([
            'pago' => ['required', Rule::in(['Pagado', 'Contra Entrega', 'Credito'])],
            'monto' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.monto' => ['nullable', 'numeric', 'min:0'],
        ]);

        $monto = round((float) $validated['monto'], 2);

        DB::transaction(function () use ($envio, $validated, $monto): void {
            $costoTotalFlete = 0.0;

            if (! empty($validated['items'])) {
                foreach ($validated['items'] as $itemData) {
                    if (! empty($itemData['id'])) {
                        $item = $envio->items()->find($itemData['id']);
                        if ($item) {
                            $fleteItem = (float) ($itemData['monto'] ?? 0);
                            $item->update([
                                'costo_transportista' => $fleteItem,
                            ]);
                            $costoTotalFlete += $fleteItem;
                        }
                    }
                }
            }

            $envio->update([
                'pago'                => $validated['pago'],
                'monto'               => $monto,
                'costo_transportista' => round($costoTotalFlete, 2),
                'liquidado_at'        => now(),
            ]);

            if ($validated['pago'] === 'Credito') {
                $existe = CuentaCorriente::query()
                    ->where('envio_id', $envio->id)
                    ->where('tipo', 'cargo')
                    ->exists();

                if (! $existe) {
                    $saldoActual = $this->calcularSaldoCliente($envio->cliente_id);
                    $nuevoSaldo = round($saldoActual + $monto, 2);

                    CuentaCorriente::create([
                        'cliente_id' => $envio->cliente_id,
                        'envio_id' => $envio->id,
                        'tipo' => 'cargo',
                        'monto' => $monto,
                        'saldo_acumulado' => $nuevoSaldo,
                        'fecha' => $envio->fecha,
                        'observacion' => 'Cargo automatico por envio '.$envio->codigo.' (Credito)',
                    ]);
                }
            } elseif ($validated['pago'] === 'Pagado') {
                $cargos = CuentaCorriente::query()
                    ->where('envio_id', $envio->id)
                    ->where('tipo', 'cargo')
                    ->sum('monto');

                if ((float) $cargos > 0) {
                    $saldoActual = $this->calcularSaldoCliente($envio->cliente_id);
                    $nuevoSaldo = round($saldoActual - $monto, 2);

                    CuentaCorriente::create([
                        'cliente_id' => $envio->cliente_id,
                        'envio_id' => $envio->id,
                        'tipo' => 'abono',
                        'monto' => $monto,
                        'saldo_acumulado' => $nuevoSaldo,
                        'fecha' => now()->format('Y-m-d'),
                        'observacion' => 'Abono automatico por envio '.$envio->codigo.' (Pagado)',
                    ]);
                }
            }
        });

        return response()->json([
            'ok' => true,
            'data' => $this->format($envio->refresh()->load(['cliente', 'transportista', 'items'])),
        ]);
    }

    public function cambiarEstado(Request $request, Envio $envio): JsonResponse
    {
        $validated = $request->validate([
            'estado' => ['required', Rule::in(self::ESTADOS)],
        ]);

        $envio->update(['estado' => $validated['estado']]);

        return response()->json([
            'ok' => true,
            'data' => $this->format($envio->refresh()->load(['cliente', 'transportista', 'items'])),
        ]);
    }

    public function pagarTransportista(Request $request, Envio $envio): JsonResponse
    {
        if (! $envio->liquidado_at) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'envio' => 'El envío debe ser liquidado antes de pagar al transportista.',
            ]);
        }

        $transportistaId = $request->input('transportista_id') ?? $envio->transportista_id;

        // Obtener ítems pendientes para este transportista en este envío
        $itemsQuery = $envio->items()->whereNull('pagado_transportista_at');
        if ($transportistaId) {
            $itemsQuery->where('transportista_id', $transportistaId);
        }
        $itemsPendientes = $itemsQuery->get();

        // Si no tiene ítems fraccionados pero tiene transportista a nivel cabecera
        if ($itemsPendientes->isEmpty() && $envio->items()->count() === 0) {
            if ($envio->pagado_transportista_at) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'envio' => 'Este envío ya fue pagado al transportista.',
                ]);
            }
        } elseif ($itemsPendientes->isEmpty()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'envio' => 'Todos los ítems de este transportista en este envío ya fueron pagados.',
            ]);
        }

        $resultado = DB::transaction(function () use ($envio, $itemsPendientes, $transportistaId, $request) {
            $montoTotal = $itemsPendientes->isNotEmpty()
                ? round((float) $itemsPendientes->sum('costo_transportista'), 2)
                : round((float) ($envio->costo_transportista ?? 0), 2);

            // 1. Crear el recibo de auditoría
            $pago = PagoTransportista::create([
                'transportista_id' => $transportistaId,
                'user_id'          => $request->user()?->id,
                'monto_total'      => $montoTotal,
                'observacion'      => "Abono individual Envío {$envio->codigo}",
                'fecha_pago'       => now(),
            ]);

            // 2. Marcar los ítems
            if ($itemsPendientes->isNotEmpty()) {
                EnvioItem::whereIn('id', $itemsPendientes->pluck('id'))->update([
                    'pago_transportista_id'   => $pago->id,
                    'pagado_transportista_at' => now(),
                ]);
            }

            // 3. Evaluar si el envío ya no tiene ítems pendientes con ningún fletero
            $tienePendientesGlobal = $envio->items()
                ->where('costo_transportista', '>', 0)
                ->whereNull('pagado_transportista_at')
                ->exists();

            if (! $tienePendientesGlobal) {
                $envio->update(['pagado_transportista_at' => now()]);
            }

            return $pago;
        });

        return response()->json([
            'ok'   => true,
            'data' => [
                'pago'  => $resultado,
                'envio' => $this->format($envio->refresh()->load(['cliente', 'transportista', 'items'])),
            ],
        ]);
    }

    public function pagarTransportistaLote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transportista_id' => ['required', 'integer', 'exists:transportistas,id'],
            'observacion'      => ['nullable', 'string', 'max:255'],
        ]);

        $resultado = DB::transaction(function () use ($validated, $request) {
            // 1. Obtener los ítems pendientes de pago de este transportista en envíos ya liquidados
            $itemsPendientes = EnvioItem::query()
                ->join('envios', 'envio_items.envio_id', '=', 'envios.id')
                ->where('envio_items.transportista_id', $validated['transportista_id'])
                ->whereNotNull('envios.liquidado_at')
                ->whereNull('envio_items.pagado_transportista_at')
                ->select('envio_items.*')
                ->get();

            if ($itemsPendientes->isEmpty()) {
                return [
                    'pago'             => null,
                    'items_pagados'    => 0,
                    'envios_afectados' => 0,
                ];
            }

            // 2. Calcular el total del abono
            $montoTotal = round((float) $itemsPendientes->sum('costo_transportista'), 2);

            // 3. Crear el registro maestro de auditoría
            $pago = PagoTransportista::create([
                'transportista_id' => $validated['transportista_id'],
                'user_id'          => $request->user()?->id,
                'monto_total'      => $montoTotal,
                'observacion'      => $validated['observacion'] ?? null,
                'fecha_pago'       => now(),
            ]);

            // 4. Marcar los ítems con el ID del pago y la fecha
            $itemIds = $itemsPendientes->pluck('id');
            EnvioItem::whereIn('id', $itemIds)->update([
                'pago_transportista_id'   => $pago->id,
                'pagado_transportista_at' => now(),
            ]);

            // 5. Para compatibilidad, si todos los ítems de un envío quedaron pagados, marcar la cabecera
            $envioIds = $itemsPendientes->pluck('envio_id')->unique();
            foreach ($envioIds as $envioId) {
                $tienePendientes = EnvioItem::where('envio_id', $envioId)
                    ->whereNull('pagado_transportista_at')
                    ->exists();

                if (! $tienePendientes) {
                    Envio::where('id', $envioId)->update(['pagado_transportista_at' => now()]);
                }
            }

            return [
                'pago'             => $pago,
                'items_pagados'    => $itemsPendientes->count(),
                'envios_afectados' => $envioIds->count(),
            ];
        });

        return response()->json([
            'ok'   => true,
            'data' => $resultado,
        ]);
    }

    public function transportistasConDeuda(): JsonResponse
    {
        $transportistas = \App\Models\Transportista::query()
            ->get()
            ->map(function ($transportista) {
                // Cantidad de envíos liquidados pendientes de pago para este transportista
                $enviosPendientes = EnvioItem::query()
                    ->join('envios', 'envio_items.envio_id', '=', 'envios.id')
                    ->where('envio_items.transportista_id', $transportista->id)
                    ->whereNotNull('envios.liquidado_at')
                    ->whereNull('envios.pagado_transportista_at')
                    ->distinct('envios.id')
                    ->count('envios.id');

                // Suma del flete pendiente de sus ítems
                $deudaTotal = EnvioItem::query()
                    ->join('envios', 'envio_items.envio_id', '=', 'envios.id')
                    ->where('envio_items.transportista_id', $transportista->id)
                    ->whereNotNull('envios.liquidado_at')
                    ->whereNull('envios.pagado_transportista_at')
                    ->sum('envio_items.costo_transportista');

                return [
                    'id'                => $transportista->id,
                    'nombre'            => $transportista->nombre,
                    'documento'         => $transportista->documento,
                    'telefono'          => $transportista->telefono,
                    'envios_pendientes' => $enviosPendientes,
                    'deuda_total'       => round((float) $deudaTotal, 2),
                ];
            })
            ->filter(fn ($t) => $t['envios_pendientes'] > 0 && $t['deuda_total'] > 0)
            ->values();

        return response()->json([
            'ok'   => true,
            'data' => $transportistas,
        ]);
    }

    public function enviosPendientesTransportista(int $transportistaId): JsonResponse
    {
        $envios = Envio::query()
            ->with(['cliente', 'items' => function ($q) use ($transportistaId) {
                $q->where('transportista_id', $transportistaId);
            }])
            ->whereHas('items', function ($q) use ($transportistaId) {
                $q->where('transportista_id', $transportistaId);
            })
            ->whereNotNull('liquidado_at')
            ->whereNull('pagado_transportista_at')
            ->latest('id')
            ->get()
            ->map(function (Envio $envio) use ($transportistaId) {
                $costoFlete = (float) $envio->items->sum('costo_transportista');

                return [
                    'id'                  => $envio->id,
                    'codigo'              => $envio->codigo,
                    'guia'                => $envio->guia,
                    'fecha'               => $envio->fecha?->format('Y-m-d'),
                    'cliente'             => $envio->cliente?->nombre,
                    'monto'               => (float) $envio->monto,
                    'costo_transportista' => $costoFlete,
                    'items'               => $envio->items,
                ];
            });

        return response()->json([
            'ok'   => true,
            'data' => $envios,
        ]);
    }

    public function destroy(Envio $envio): JsonResponse
    {
        $envio->delete();

        return response()->json(['ok' => true]);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => [
                'total' => Envio::query()->count(),
                'hoy' => Envio::query()->whereDate('fecha', Carbon::today())->count(),
                'contra_entrega' => Envio::query()->where('pago', 'Contra Entrega')->count(),
                'pendientes_monto' => Envio::query()->where('pago', 'Pendiente')->count(),
            ],
        ]);
    }

    public function export(Request $request): Response
    {
        $envios = $this->filteredQuery($request)
            ->latest('id')
            ->get()
            ->map(fn (Envio $envio) => $this->format($envio));

        $headers = [
            'Codigo',
            'Fecha',
            'Cliente',
            'Documento',
            'Telefono',
            'Direccion',
            'Items',
            'Guia',
            'Pago',
            ...($request->user()?->can('envios.amounts') ? ['Monto'] : []),
            'Observacion',
        ];

        $rows = $envios->map(function (array $envio) use ($request): array {
            $itemsText = collect($envio['items'] ?? [])->map(function ($item) {
                return $item['cantidad'].' - '.$item['descripcion'];
            })->implode(' | ');

            return [
                $envio['codigo'],
                $envio['fecha'],
                $envio['cliente'],
                $envio['cliente_dni'] ?? '-',
                $envio['telefono'],
                $envio['direccion'],
                $itemsText ?: '-',
                $envio['guia'],
                $envio['pago'],
                ...($request->user()?->can('envios.amounts') ? [$envio['monto'] ?? ''] : []),
                $envio['observacion'],
            ];
        });

        $content = $this->xlsx($headers, $rows->all());

        $filename = 'envios_'.now()->format('Y-m-d_H-i-s').'.xlsx';

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($content),
        ]);
    }

    private function payload(Request $request, ?Envio $envio = null): array
    {
        $rules = [
            'codigo' => ['nullable', 'string', 'max:20'],
            'fecha' => ['required', 'date'],
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'guia' => [
                'required',
                'string',
                'max:40',
                'regex:/^[A-Za-z0-9-]+$/',
                Rule::unique('envios', 'guia')->ignore($envio?->id),
            ],
            'observacion' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.descripcion' => ['required', 'string'],
            'items.*.monto' => ['nullable', 'numeric', 'min:0'],
        ];

        $validated = $request->validate($rules, [
            'guia.required' => 'La guia de remitente es obligatoria.',
            'guia.regex' => 'La guia de remitente solo acepta letras, numeros y guiones.',
            'guia.unique' => 'La guia ingresada ya existe.',
            'items.required' => 'Debe agregar al menos un item.',
            'items.min' => 'Debe agregar al menos un item.',
            'items.*.cantidad.required' => 'La cantidad es obligatoria.',
            'items.*.cantidad.integer' => 'La cantidad debe ser un número entero.',
            'items.*.cantidad.min' => 'La cantidad debe ser al menos 1.',
            'items.*.descripcion.required' => 'La descripción es obligatoria.',
            'items.*.monto.numeric' => 'El monto del item debe ser un número.',
            'items.*.monto.min' => 'El monto del item no puede ser negativo.',
        ]);

        $data = [
            'codigo' => trim((string) ($validated['codigo'] ?? '')) ?: $this->nextCode(),
            'fecha' => $validated['fecha'],
            'guia' => strtoupper(trim($validated['guia'])),
            'observacion' => trim((string) ($validated['observacion'] ?? '')) ?: null,
        ];

        if (! $envio) {
            $data['pago'] = 'Pendiente';
        }

        return $data;
    }

    private function nextCode(): string
    {
        $next = ((int) Envio::query()->max('id')) + 1;

        return 'ENV-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function filteredQuery(Request $request): Builder
    {
        $q = trim((string) $request->query('q', ''));
        $pago = trim((string) $request->query('pago', ''));
        $cliente = trim((string) $request->query('cliente', ''));
        $transportista = trim((string) $request->query('transportista', ''));
        $fechaDesde = trim((string) $request->query('fecha_desde', ''));
        $fechaHasta = trim((string) $request->query('fecha_hasta', ''));

        return Envio::query()
            ->with(['cliente', 'transportista', 'items'])
            ->when($q !== '', function (Builder $query) use ($q): void {
                $query->where(function (Builder $query) use ($q): void {
                    $query->where('codigo', 'like', "%{$q}%")
                        ->orWhere('guia', 'like', "%{$q}%")
                        ->orWhereHas('cliente', function (Builder $query) use ($q): void {
                            $query->where('nombre', 'like', "%{$q}%")
                                ->orWhere('dni', 'like', "%{$q}%");
                        })
                        ->orWhereHas('transportista', function (Builder $query) use ($q): void {
                            $query->where('nombre', 'like', "%{$q}%")
                                ->orWhere('documento', 'like', "%{$q}%");
                        })
                        ->orWhereHas('items', function (Builder $query) use ($q): void {
                            $query->where('descripcion', 'like', "%{$q}%");
                        });
                });
            })
            ->when($pago !== '', fn (Builder $query) => $query->where('pago', $pago))
            ->when($cliente !== '', function (Builder $query) use ($cliente): void {
                $query->whereHas('cliente', function (Builder $query) use ($cliente): void {
                    $query->where('nombre', 'like', "%{$cliente}%")
                        ->orWhere('dni', 'like', "%{$cliente}%");
                });
            })
            ->when($transportista !== '', function (Builder $query) use ($transportista): void {
                $query->whereHas('transportista', function (Builder $query) use ($transportista): void {
                    $query->where('nombre', 'like', "%{$transportista}%")
                        ->orWhere('documento', 'like', "%{$transportista}%");
                });
            })
            ->when($fechaDesde !== '', fn (Builder $query) => $query->whereDate('fecha', '>=', $fechaDesde))
            ->when($fechaHasta !== '', fn (Builder $query) => $query->whereDate('fecha', '<=', $fechaHasta));
    }

    private function xlsx(array $headers, array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'envios_');
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::OVERWRITE);

        $sheetRows = array_merge([$headers], $rows === [] ? [array_fill(0, count($headers), '')] : $rows);
        [$sharedStrings, $sharedMap] = $this->sharedStrings($sheetRows);

        $zip->addFromString('[Content_Types].xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/tables/table1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.table+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
  <Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
</Types>
XML);
        $zip->addFromString('_rels/.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML);
        $zip->addFromString('xl/workbook.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Envios" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>
XML);
        $zip->addFromString('xl/_rels/workbook.xml.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
</Relationships>
XML);
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/sharedStrings.xml', $this->sharedStringsXml($sharedStrings, count($sheetRows) * count($headers)));
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->worksheetXml($headers, $rows, $sharedMap));
        $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/table" Target="../tables/table1.xml"/>
</Relationships>
XML);
        $zip->addFromString('xl/tables/table1.xml', $this->tableXml($headers, count($rows)));
        $zip->close();

        $content = file_get_contents($path);
        unlink($path);

        return $content;
    }

    private function worksheetXml(array $headers, array $rows, array $sharedMap): string
    {
        if ($rows === []) {
            $rows = [array_fill(0, count($headers), '')];
        }

        $sheetRows = array_merge([$headers], $rows);
        $xmlRows = [];
        $lastColumn = $this->columnName(count($headers));
        $lastRow = count($sheetRows);
        $range = 'A1:'.$lastColumn.$lastRow;

        foreach ($sheetRows as $index => $row) {
            $rowNumber = $index + 1;
            $cells = [];

            foreach (array_values($row) as $columnIndex => $value) {
                $cell = $this->columnName($columnIndex + 1).$rowNumber;
                $sharedIndex = $sharedMap[(string) ($value ?? '')];
                $style = $rowNumber === 1 ? 1 : ($rowNumber % 2 === 0 ? 2 : 3);
                $cells[] = '<c r="'.$cell.'" s="'.$style.'" t="s"><v>'.$sharedIndex.'</v></c>';
            }

            $xmlRows[] = '<row r="'.$rowNumber.'">'.implode('', $cells).'</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<dimension ref="'.$range.'"/>'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<cols><col min="1" max="1" width="16" customWidth="1"/><col min="2" max="2" width="14" customWidth="1"/><col min="3" max="8" width="20" customWidth="1"/><col min="9" max="14" width="18" customWidth="1"/></cols>'
            .'<sheetData>'.implode('', $xmlRows).'</sheetData>'
            .'<tableParts count="1"><tablePart r:id="rId1"/></tableParts>'
            .'</worksheet>';
    }

    private function sharedStrings(array $rows): array
    {
        $strings = [];
        $map = [];

        foreach ($rows as $row) {
            foreach (array_values($row) as $value) {
                $value = (string) ($value ?? '');
                if (! array_key_exists($value, $map)) {
                    $map[$value] = count($strings);
                    $strings[] = $value;
                }
            }
        }

        return [$strings, $map];
    }

    private function sharedStringsXml(array $strings, int $totalCount): string
    {
        $items = array_map(
            fn (string $value) => '<si><t>'.htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8').'</t></si>',
            $strings,
        );

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'.$totalCount.'" uniqueCount="'.count($strings).'">'
            .implode('', $items)
            .'</sst>';
    }

    private function stylesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="2">
    <font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>
    <font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>
  </fonts>
  <fills count="4">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF2F5597"/><bgColor indexed="64"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFD9E2F3"/><bgColor indexed="64"/></patternFill></fill>
  </fills>
  <borders count="2">
    <border><left/><right/><top/><bottom/><diagonal/></border>
    <border>
      <left style="thin"><color rgb="FFB7C9E2"/></left>
      <right style="thin"><color rgb="FFB7C9E2"/></right>
      <top style="thin"><color rgb="FFB7C9E2"/></top>
      <bottom style="thin"><color rgb="FFB7C9E2"/></bottom>
      <diagonal/>
    </border>
  </borders>
  <cellStyleXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
  </cellStyleXfs>
  <cellXfs count="4">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>
    <xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1"/>
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>
  </cellXfs>
  <cellStyles count="1">
    <cellStyle name="Normal" xfId="0" builtinId="0"/>
  </cellStyles>
  <dxfs count="0"/>
  <tableStyles count="0" defaultTableStyle="TableStyleMedium2" defaultPivotStyle="PivotStyleLight16"/>
</styleSheet>
XML;
    }

    private function tableXml(array $headers, int $rowCount): string
    {
        $lastColumn = $this->columnName(count($headers));
        $lastRow = max(2, $rowCount + 1);
        $range = 'A1:'.$lastColumn.$lastRow;
        $columns = [];

        foreach ($headers as $index => $header) {
            $columns[] = '<tableColumn id="'.($index + 1).'" name="'.
                htmlspecialchars((string) $header, ENT_XML1 | ENT_COMPAT, 'UTF-8').'"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<table xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" id="1" name="TablaEnvios" displayName="TablaEnvios" ref="'.$range.'" headerRowCount="1" totalsRowShown="0">'
            .'<autoFilter ref="'.$range.'"/>'
            .'<tableColumns count="'.count($headers).'">'.implode('', $columns).'</tableColumns>'
            .'<tableStyleInfo name="TableStyleMedium2" showFirstColumn="0" showLastColumn="0" showRowStripes="1" showColumnStripes="0"/>'
            .'</table>';
    }

    private function columnName(int $number): string
    {
        $name = '';

        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)).$name;
            $number = intdiv($number, 26);
        }

        return $name;
    }

    private function format(Envio $envio): array
    {
        return [
            'id' => $envio->id,
            'codigo' => $envio->codigo,
            'fecha' => $envio->fecha?->format('Y-m-d'),
            'cliente_id' => $envio->cliente_id,
            'cliente_dni' => $envio->cliente?->dni,
            'cliente_tipo_documento' => $envio->cliente?->tipo_documento,
            'cliente' => $envio->cliente?->nombre,
            'telefono' => $envio->cliente?->telefono,
            'direccion' => $envio->cliente?->direccion,
            'transportista_id' => $envio->transportista_id,
            'transportista' => $envio->transportista?->nombre,
            'transportista_documento' => $envio->transportista?->documento,
            'transportista_telefono' => $envio->transportista?->telefono,
            'items' => $envio->items->map(fn ($item) => [
                'id' => $item->id,
                'cantidad' => $item->cantidad,
                'descripcion' => $item->descripcion,
                'monto' => $item->monto,
            ])->values(),
            'guia' => $envio->guia,
            'pago' => $envio->pago,
            'estado' => $envio->estado ?? 'Registrado',
            'monto' => request()->user()?->can('envios.amounts') ? $envio->monto : null,
            'costo_transportista' => request()->user()?->can('envios.amounts') ? $envio->costo_transportista : null,
            'margen' => request()->user()?->can('envios.amounts') ? $envio->margen : null,
            'liquidado_at' => request()->user()?->can('envios.amounts') ? $envio->liquidado_at?->format('Y-m-d H:i:s') : null,
            'pagado_transportista_at' => request()->user()?->can('envios.amounts') ? $envio->pagado_transportista_at?->format('Y-m-d H:i:s') : null,
            'observacion' => $envio->observacion,
        ];
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

        return round(((float) $cargos - (float) $abonos), 2);
    }
}
