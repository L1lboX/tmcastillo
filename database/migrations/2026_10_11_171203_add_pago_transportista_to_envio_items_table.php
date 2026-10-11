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
        Schema::table('envio_items', function (Blueprint $table) {
           $table->foreignId('pago_transportista_id')
                ->nullable()
                ->after('transportista_id')
                ->constrained('pago_transportistas')
                ->nullOnDelete();
            $table->timestamp('pagado_transportista_at')
                ->nullable()
                ->after('pago_transportista_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('envio_items', function (Blueprint $table) {
            $table->dropForeign(['pago_transportista_id']);
            $table->dropColumn(['pago_transportista_id', 'pagado_transportista_at']);
        });
    }
};
