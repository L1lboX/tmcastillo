<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PagoTransportista extends Model
{
    protected $table = 'pago_transportistas';

    protected $fillable = [
        'transportista_id',
        'user_id',
        'monto_total',
        'observacion',
        'fecha_pago',
    ];

    protected function casts(): array
    {
        return [
            'transportista_id' => 'integer',
            'user_id'          => 'integer',
            'monto_total'      => 'decimal:2',
            'fecha_pago'       => 'datetime',
        ];
    }

    public function transportista(): BelongsTo
    {
        return $this->belongsTo(Transportista::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(EnvioItem::class, 'pago_transportista_id');
    }

   
}
