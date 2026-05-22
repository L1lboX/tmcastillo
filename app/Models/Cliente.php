<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['dni', 'nombre', 'telefono', 'direccion'])]
class Cliente extends Model
{
    protected $primaryKey = 'dni';

    public $incrementing = false;

    protected $keyType = 'string';

    public function envios(): HasMany
    {
        return $this->hasMany(Envio::class, 'cliente_dni', 'dni');
    }
}
