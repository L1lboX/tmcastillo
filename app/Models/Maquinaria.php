<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['placa', 'descripcion', 'activo'])]
class Maquinaria extends Model
{
    protected $table = 'maquinarias';

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function alquileres(): HasMany
    {
        return $this->hasMany(Alquiler::class, 'maquinaria_id');
    }
}