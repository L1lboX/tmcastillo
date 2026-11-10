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
        Schema::table('cuentas_corrientes', function (Blueprint $table) {
            
            $table->foreignId('alquiler_id')
                ->nullable()
                ->after('envio_id')
                ->constrained('alquileres')
                ->nullOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuentas_corrientes', function (Blueprint $table) {
            $table->dropForeign(['alquiler_id']);
            $table->dropColumn('alquiler_id');
        });
    }
};
