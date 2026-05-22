<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table): void {
            $table->string('dni', 12)->primary();
            $table->string('nombre', 120);
            $table->string('telefono', 20)->nullable();
            $table->string('direccion', 180)->nullable();
            $table->timestamps();
        });

        Schema::create('envios', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->date('fecha');
            $table->string('cliente_dni', 12);
            $table->unsignedInteger('cantidad');
            $table->string('tipo', 80);
            $table->string('especificacion_tamano', 40)->nullable();
            $table->string('especificacion_peso', 40)->nullable();
            $table->text('detalle');
            $table->string('guia', 40)->unique();
            $table->string('pago', 20);
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->foreign('cliente_dni')
                ->references('dni')
                ->on('clientes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->index('fecha');
            $table->index('pago');
            $table->index('cliente_dni');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('envios');
        Schema::dropIfExists('clientes');
    }
};
