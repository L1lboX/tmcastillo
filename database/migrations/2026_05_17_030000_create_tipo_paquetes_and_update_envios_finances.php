<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_paquetes', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 80)->unique();
            $table->decimal('precio_transportista', 10, 2)->default(0);
            $table->string('descripcion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::table('envios', function (Blueprint $table): void {
            $table->foreignId('tipo_paquete_id')->nullable()->after('tipo')->constrained('tipo_paquetes')->nullOnDelete();
            $table->decimal('costo_transportista', 10, 2)->nullable()->after('monto');
            $table->decimal('margen', 10, 2)->nullable()->after('costo_transportista');
            $table->timestamp('liquidado_at')->nullable()->after('margen');
        });

        DB::table('envios')->whereNull('pago')->orWhere('pago', '')->update(['pago' => 'Pendiente']);
    }

    public function down(): void
    {
        Schema::table('envios', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tipo_paquete_id');
            $table->dropColumn(['costo_transportista', 'margen', 'liquidado_at']);
        });

        Schema::dropIfExists('tipo_paquetes');
    }
};
