<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gastos', function (Blueprint $table): void {
            $table->id();
            $table->date('fecha');
            $table->string('tipo', 50);
            $table->decimal('monto', 10, 2);
            $table->text('detalle')->nullable();
            $table->string('destino', 150);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('fecha');
            $table->index('tipo');
            $table->index('destino');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
