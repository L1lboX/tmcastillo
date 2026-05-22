<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transportistas', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 120);
            $table->string('telefono', 20)->nullable();
            $table->string('documento', 20)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('nombre');
        });

        Schema::table('envios', function (Blueprint $table): void {
            $table->foreignId('transportista_id')
                ->nullable()
                ->after('cliente_dni')
                ->constrained('transportistas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('envios', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('transportista_id');
        });

        Schema::dropIfExists('transportistas');
    }
};
