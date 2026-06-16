<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('envio_items', function (Blueprint $table) {
            $table->foreignId('transportista_id')
                ->nullable()
                ->after('envio_id')
                ->constrained('transportistas')
                ->nullOnDelete();
            $table->index('transportista_id');
        });
    }

    public function down(): void
    {
        Schema::table('envio_items', function (Blueprint $table) {
            $table->dropForeign(['transportista_id']);
            $table->dropColumn('transportista_id');
        });
    }
};
