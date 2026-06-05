<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'telefono', 'documento', 'tipo_documento', 'tipos', 'activo'])]
class Transportista extends Model
{
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'tipos' => 'array',
        ];
    }

    public function envios(): HasMany
    {
        return $this->hasMany(Envio::class);
    }
}
