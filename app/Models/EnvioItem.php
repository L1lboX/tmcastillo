<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['envio_id', 'transportista_id', 'cantidad', 'descripcion', 'monto'])]
class EnvioItem extends Model
{
    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'monto' => 'decimal:2',
            'transportista_id' => 'integer',
        ];
    }

    public function envio(): BelongsTo
    {
        return $this->belongsTo(Envio::class);
    }

    public function transportista(): BelongsTo
    {
        return $this->belongsTo(Transportista::class);
    }
}
