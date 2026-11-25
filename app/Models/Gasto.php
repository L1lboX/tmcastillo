<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gasto extends Model
{
    protected $table = 'gastos';

    public const TIPOS = [
        'Cargadores',
        'Chofer',
        'Combustible',
        'Otros',
        'Préstamo',
        'Reparaciones',
        'Repuestos',
        'Secretaria',
        'Viáticos',
    ];

    protected $fillable = [
        'fecha',
        'tipo',
        'monto',
        'detalle',
        'destino',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha'   => 'date:Y-m-d',
            'monto'   => 'decimal:2',
            'user_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
