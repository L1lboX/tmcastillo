<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'codigo',
    'fecha',
    'cliente_id',
    'transportista_id',
    'cantidad',
    'tipo',
    'tipo_paquete_id',
    'especificacion_tamano',
    'especificacion_peso',
    'detalle',
    'guia',
    'pago',
    'monto',
    'costo_transportista',
    'margen',
    'liquidado_at',
    'observacion',
])]
class Envio extends Model
{
    protected function casts(): array
    {
        return [
            'fecha' => 'date:Y-m-d',
            'cantidad' => 'integer',
            'cliente_id' => 'integer',
            'monto' => 'decimal:2',
            'costo_transportista' => 'decimal:2',
            'margen' => 'decimal:2',
            'liquidado_at' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function transportista(): BelongsTo
    {
        return $this->belongsTo(Transportista::class);
    }

    public function tipoPaquete(): BelongsTo
    {
        return $this->belongsTo(TipoPaquete::class);
    }

    public function cuentaCorriente(): HasOne
    {
        return $this->hasOne(CuentaCorriente::class);
    }
}
