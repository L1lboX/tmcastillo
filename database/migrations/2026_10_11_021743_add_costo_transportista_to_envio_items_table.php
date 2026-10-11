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
            $table->decimal('costo_transportista', 10, 2)->default(0)->after('monto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('envio_items', function (Blueprint $table) {
            $table->dropColumn('costo_transportista');
        });
    }
};
