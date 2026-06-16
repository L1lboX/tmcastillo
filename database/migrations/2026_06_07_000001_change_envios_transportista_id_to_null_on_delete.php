<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('envios', function (Blueprint $table) {
            $table->dropForeign(['transportista_id']);
            $table->foreign('transportista_id')->references('id')->on('transportistas')->nullOnDelete();
            $table->unsignedBigInteger('transportista_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('envios', function (Blueprint $table) {
            $table->dropForeign(['transportista_id']);
            $table->foreign('transportista_id')->references('id')->on('transportistas')->restrictOnDelete();
            $table->unsignedBigInteger('transportista_id')->nullable(false)->change();
        });
    }
};
