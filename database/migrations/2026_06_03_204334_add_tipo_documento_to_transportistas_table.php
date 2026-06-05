<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transportistas', function (Blueprint $table): void {
            $table->string('tipo_documento', 3)->nullable()->after('documento');
        });
    }

    public function down(): void
    {
        Schema::table('transportistas', function (Blueprint $table): void {
            $table->dropColumn('tipo_documento');
        });
    }
};
