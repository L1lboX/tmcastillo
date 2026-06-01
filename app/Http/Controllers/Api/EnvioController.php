<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CuentaCorriente;
use App\Models\Envio;
use App\Models\TipoPaquete;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EnvioController extends Controller
{
    private const PAGOS = ['Pendiente', 'Pagado', 'Contra Entrega', 'Credito'];

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
            'data' => $this->format($envio->load(['cliente', 'transportista'])),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $envio = DB::transaction(function () use ($request): Envio {
            ClienteController::upsertFromEnvio($request);

            return Envio::query()->create($this->payload($request));
        });

        return response()->json([
            'ok' => true,
            'data' => $this->format($envio->load(['cliente', 'transportista'])),
        ], 201);
    }

    public function update(Request $request, Envio $envio): JsonResponse
    {
        DB::transaction(function () use ($request, $envio): void {
            ClienteController::upsertFromEnvio($request);
            $envio->update($this->payload($request, $envio));
        });

        return response()->json([
            'ok' => true,
            'data' => $this->format($envio->refresh()->load(['cliente', 'transportista'])),
        ]);
    }

    public function liquidar(Request $request, Envio $envio): JsonResponse
    {
        $validated = $request->validate([
            'pago' => ['required', Rule::in(['Pagado', 'Contra Entrega', 'Credito'])],
            'monto' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        $envio->load('tipoPaquete');
        $costoTransportista = $this->costoTransportista($envio);
        $monto = round((float) $validated['monto'], 2);

        DB::transaction(function () use ($envio, $validated, $costoTransportista, $monto): void {
            $envio->update([
                'pago' => $validated['pago'],
                'monto' => $monto,
                'costo_transportista' => $costoTransportista,
                'margen' => round($monto - $costoTransportista, 2),
                'liquidado_at' => now(),
            ]);

            if ($validated['pago'] === 'Credito') {
                $existe = CuentaCorriente::query()
                    ->where('envio_id', $envio->id)
                    ->where('tipo', 'cargo')
                    ->exists();

                if (! $existe) {
                    $ultimoSaldo = CuentaCorriente::query()
                        ->where('cliente_dni', $envio->cliente_dni)
                        ->latest('id')
                        ->value('saldo_acumulado');

                    $saldoActual = $ultimoSaldo !== null ? (float) $ultimoSaldo : $this->calcularSaldoCliente($envio->cliente_dni);
                    $nuevoSaldo = round($saldoActual + $monto, 2);

                    CuentaCorriente::create([
                        'cliente_dni' => $envio->cliente_dni,
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
                    $ultimoSaldo = CuentaCorriente::query()
                        ->where('cliente_dni', $envio->cliente_dni)
                        ->latest('id')
                        ->value('saldo_acumulado');

                    $saldoActual = $ultimoSaldo !== null ? (float) $ultimoSaldo : $this->calcularSaldoCliente($envio->cliente_dni);
                    $nuevoSaldo = round($saldoActual - $monto, 2);

                    CuentaCorriente::create([
                        'cliente_dni' => $envio->cliente_dni,
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
            'data' => $this->format($envio->refresh()->load(['cliente', 'transportista', 'tipoPaquete'])),
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
            'DNI',
            'Telefono',
            'Direccion',
            'Transportista',
            'Cantidad',
            'Tipo',
            'Especificacion',
            'Detalle',
            'Guia',
            'Pago',
            ...($request->user()?->can('envios.amounts') ? ['Monto'] : []),
            'Observacion',
        ];

        $rows = $envios->map(function (array $envio) use ($request): array {
            return [
                $envio['codigo'],
                $envio['fecha'],
                $envio['cliente'],
                $envio['cliente_dni'],
                $envio['telefono'],
                $envio['direccion'],
                $envio['transportista'],
                $envio['cantidad'],
                $envio['tipo'],
                $envio['especificacion'],
                $envio['detalle'],
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
            'cliente_dni' => ['required', 'string', 'size:8', 'regex:/^\d+$/'],
            'transportista_id' => ['nullable', 'integer', 'exists:transportistas,id'],
            'cantidad' => ['required', 'integer', 'min:1'],
            'tipo' => ['required', 'string', 'max:80'],
            'tipo_paquete_id' => ['nullable', 'integer', 'exists:tipo_paquetes,id'],
            'especificacion_tamano' => ['nullable', 'string', 'max:40'],
            'especificacion_peso' => ['nullable', 'string', 'max:40'],
            'detalle' => ['required', 'string'],
            'guia' => [
                'required',
                'string',
                'max:40',
                'regex:/^[A-Za-z0-9-]+$/',
                Rule::unique('envios', 'guia')->ignore($envio?->id),
            ],
            'pago' => ['nullable', Rule::in(self::PAGOS)],
            'monto' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'observacion' => ['nullable', 'string'],
        ];

        $validated = $request->validate($rules, [
            'guia.required' => 'La guia de remitente es obligatoria.',
            'guia.regex' => 'La guia de remitente solo acepta letras, numeros y guiones.',
            'guia.unique' => 'La guia ingresada ya existe.',
            'pago.in' => 'El estado de pago no es valido.',
        ]);

        $tipoPaqueteId = $validated['tipo_paquete_id'] ?? null;
        if (! $tipoPaqueteId) {
            $tipoPaqueteId = TipoPaquete::query()
                ->where('nombre', trim($validated['tipo']))
                ->value('id');
        }

        $data = [
            'codigo' => trim((string) ($validated['codigo'] ?? '')) ?: $this->nextCode(),
            'fecha' => $validated['fecha'],
            'cliente_dni' => trim($validated['cliente_dni']),
            'transportista_id' => $validated['transportista_id'] ?? null,
            'cantidad' => (int) $validated['cantidad'],
            'tipo' => trim($validated['tipo']),
            'tipo_paquete_id' => $tipoPaqueteId,
            'especificacion_tamano' => trim((string) ($validated['especificacion_tamano'] ?? '')) ?: null,
            'especificacion_peso' => trim((string) ($validated['especificacion_peso'] ?? '')) ?: null,
            'detalle' => trim($validated['detalle']),
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
        $tipo = trim((string) $request->query('tipo', ''));
        $cliente = trim((string) $request->query('cliente', ''));
        $transportista = trim((string) $request->query('transportista', ''));
        $fechaDesde = trim((string) $request->query('fecha_desde', ''));
        $fechaHasta = trim((string) $request->query('fecha_hasta', ''));

        return Envio::query()
            ->with(['cliente', 'transportista', 'tipoPaquete'])
            ->when($q !== '', function (Builder $query) use ($q): void {
                $query->where(function (Builder $query) use ($q): void {
                    $query->where('codigo', 'like', "%{$q}%")
                        ->orWhere('guia', 'like', "%{$q}%")
                        ->orWhere('tipo', 'like', "%{$q}%")
                        ->orWhere('detalle', 'like', "%{$q}%")
                        ->orWhereHas('cliente', function (Builder $query) use ($q): void {
                            $query->where('nombre', 'like', "%{$q}%")
                                ->orWhere('dni', 'like', "%{$q}%");
                        })
                        ->orWhereHas('transportista', function (Builder $query) use ($q): void {
                            $query->where('nombre', 'like', "%{$q}%")
                                ->orWhere('documento', 'like', "%{$q}%");
                        });
                });
            })
            ->when($pago !== '', fn (Builder $query) => $query->where('pago', $pago))
            ->when($tipo !== '', fn (Builder $query) => $query->where('tipo', 'like', "%{$tipo}%"))
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
        $tamano = $envio->especificacion_tamano;
        $peso = $envio->especificacion_peso;

        return [
            'id' => $envio->id,
            'codigo' => $envio->codigo,
            'fecha' => $envio->fecha?->format('Y-m-d'),
            'cliente_dni' => $envio->cliente_dni,
            'cliente' => $envio->cliente?->nombre,
            'telefono' => $envio->cliente?->telefono,
            'direccion' => $envio->cliente?->direccion,
            'transportista_id' => $envio->transportista_id,
            'transportista' => $envio->transportista?->nombre,
            'transportista_documento' => $envio->transportista?->documento,
            'transportista_telefono' => $envio->transportista?->telefono,
            'cantidad' => $envio->cantidad,
            'tipo_paquete_id' => $envio->tipo_paquete_id,
            'tipo' => $envio->tipo,
            'precio_transportista' => $envio->tipoPaquete?->precio_transportista,
            'especificacion_tamano' => $tamano,
            'especificacion_peso' => $peso,
            'especificacion' => collect([$tamano, $peso])->filter()->join(' / ') ?: '-',
            'detalle' => $envio->detalle,
            'guia' => $envio->guia,
            'pago' => $envio->pago,
            'monto' => request()->user()?->can('envios.amounts') ? $envio->monto : null,
            'costo_transportista' => request()->user()?->can('envios.amounts') ? $envio->costo_transportista : null,
            'margen' => request()->user()?->can('envios.amounts') ? $envio->margen : null,
            'liquidado_at' => request()->user()?->can('envios.amounts') ? $envio->liquidado_at?->format('Y-m-d H:i:s') : null,
            'observacion' => $envio->observacion,
        ];
    }

    private function costoTransportista(Envio $envio): float
    {
        $precio = (float) ($envio->tipoPaquete?->precio_transportista ?? 0);

        return round($envio->cantidad * $precio, 2);
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

        $enviosCredito = Envio::query()
            ->where('cliente_dni', $dni)
            ->where('pago', 'Credito')
            ->whereNotNull('monto')
            ->sum('monto');

        return round(((float) $cargos + (float) $enviosCredito - (float) $abonos), 2);
    }
}
