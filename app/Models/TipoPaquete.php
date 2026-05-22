<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'precio_transportista', 'descripcion', 'activo'])]
class TipoPaquete extends Model
{
    protected function casts(): array
    {
        return [
            'precio_transportista' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function envios(): HasMany
    {
        return $this->hasMany(Envio::class);
    }
}
