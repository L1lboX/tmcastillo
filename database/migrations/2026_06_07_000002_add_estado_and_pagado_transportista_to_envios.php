<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('envios', function (Blueprint $table) {
            $table->string('estado', 20)->default('Registrado')->after('pago');
            $table->timestamp('pagado_transportista_at')->nullable()->after('liquidado_at');
        });
    }

    public function down(): void
    {
        Schema::table('envios', function (Blueprint $table) {
            $table->dropColumn(['estado', 'pagado_transportista_at']);
        });
    }
};
