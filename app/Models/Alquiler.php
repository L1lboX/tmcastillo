<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'dia',
    'cliente_id',   
    'maquinaria_id',
    'horas',
    'precio',
    'importe_proyecto',
    'proyecto',    
    'comprobante',
    'cuenta',
    'destino_pago',
    'observacion',
])]
class Alquiler extends Model
{
    protected $table = 'alquileres';

    protected static function booted(): void
    {
        static::saving(function (Alquiler $alquiler): void {
            $horas = round((float) $alquiler->horas, 2);
            $precio = round((float) $alquiler->precio, 3);

            $alquiler->horas = $horas;
            $alquiler->precio = $precio;
            $alquiler->importe_proyecto = round($horas * $precio, 3);
        });
    }

    protected function casts(): array
    {
        return [
            'dia' => 'date:Y-m-d',
            'cliente_id' => 'integer',
            'maquinaria_id' => 'integer',
            'horas' => 'decimal:2',
            'precio' => 'decimal:3',
            'importe_proyecto' => 'decimal:3',
        ];

    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function maquinaria(): BelongsTo
    {
        return $this->belongsTo(Maquinaria::class, 'maquinaria_id');
    }

    public function cuentasCorrientes(): HasMany
    {
        return $this->hasMany(CuentaCorriente::class, 'alquiler_id');
    }

}