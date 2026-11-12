<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alquileres', function (Blueprint $table) {
	    $table->engine = 'InnoDB';
            $table->id();
            $table->date('dia');
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('restrict');
            $table->foreignId('maquinaria_id')->constrained('maquinarias')->onDelete('restrict');
            $table->decimal('horas', 8, 2);
            $table->decimal('precio', 10, 3);
            $table->decimal('importe_proyecto', 10, 3);
            $table->string('proyecto', 150);
            $table->string('comprobante', 50); 
            $table->string('cuenta', 50);      
            $table->string('destino_pago', 50); 
            $table->text('observacion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alquileres');
    }
};
