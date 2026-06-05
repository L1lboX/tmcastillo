<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'cliente_id',
    'envio_id',
    'tipo',
    'monto',
    'saldo_acumulado',
    'fecha',
    'observacion',
])]
class CuentaCorriente extends Model
{
    protected $table = 'cuentas_corrientes';

    protected function casts(): array
    {
        return [
            'cliente_id' => 'integer',
            'envio_id' => 'integer',
            'fecha' => 'date:Y-m-d',
            'monto' => 'decimal:2',
            'saldo_acumulado' => 'decimal:2',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function envio(): BelongsTo
    {
        return $this->belongsTo(Envio::class);
    }
}
