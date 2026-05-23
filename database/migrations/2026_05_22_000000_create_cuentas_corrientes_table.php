<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas_corrientes', function (Blueprint $table): void {
            $table->id();
            $table->string('cliente_dni', 12);
            $table->foreignId('envio_id')->nullable()->constrained('envios')->nullOnDelete();
            $table->enum('tipo', ['cargo', 'abono']);
            $table->decimal('monto', 10, 2);
            $table->decimal('saldo_acumulado', 10, 2)->default(0);
            $table->date('fecha');
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->foreign('cliente_dni')->references('dni')->on('clientes')->cascadeOnDelete();
            $table->index(['cliente_dni', 'fecha']);
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas_corrientes');
    }
};
