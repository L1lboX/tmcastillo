<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('envio_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('envio_id')->constrained('envios')->cascadeOnDelete();
            $table->unsignedInteger('cantidad');
            $table->text('descripcion');
            $table->timestamps();

            $table->index('envio_id');
        });

        Schema::table('envios', function (Blueprint $table): void {
            $table->dropForeign(['tipo_paquete_id']);
        });

        Schema::table('envios', function (Blueprint $table): void {
            $table->dropColumn([
                'tipo',
                'especificacion_tamano',
                'especificacion_peso',
                'detalle',
                'tipo_paquete_id',
                'cantidad',
            ]);
        });

        Schema::dropIfExists('tipo_paquetes');
    }

    public function down(): void
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
            $table->unsignedInteger('cantidad')->nullable()->after('cliente_id');
            $table->foreignId('tipo_paquete_id')->nullable()->after('tipo')->constrained('tipo_paquetes')->nullOnDelete();
            $table->string('tipo', 80)->nullable()->after('tipo_paquete_id');
            $table->string('especificacion_tamano', 40)->nullable()->after('tipo');
            $table->string('especificacion_peso', 40)->nullable()->after('especificacion_tamano');
            $table->text('detalle')->nullable()->after('especificacion_peso');
        });

        Schema::dropIfExists('envio_items');
    }
};
