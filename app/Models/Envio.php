<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'codigo',
    'fecha',
    'cliente_id',
    'transportista_id',
    'guia',
    'pago',
    'estado',
    'monto',
    'costo_transportista',
    'margen',
    'liquidado_at',
    'pagado_transportista_at',
    'observacion',
])]
class Envio extends Model
{
    protected function casts(): array
    {
        return [
            'fecha' => 'date:Y-m-d',
            'cliente_id' => 'integer',
            'monto' => 'decimal:2',
            'costo_transportista' => 'decimal:2',
            'margen' => 'decimal:2',
            'liquidado_at' => 'datetime',
            'pagado_transportista_at' => 'datetime',
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

    public function items(): HasMany
    {
        return $this->hasMany(EnvioItem::class);
    }

    public function cuentaCorriente(): HasOne
    {
        return $this->hasOne(CuentaCorriente::class);
    }
}
