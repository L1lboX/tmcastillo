<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['dni', 'tipo_documento', 'nombre', 'telefono', 'direccion', 'activo'])]
class Cliente extends Model
{
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function envios(): HasMany
    {
        return $this->hasMany(Envio::class, 'cliente_id');
    }

    public function cuentasCorrientes(): HasMany
    {
        return $this->hasMany(CuentaCorriente::class, 'cliente_id');
    }
}
