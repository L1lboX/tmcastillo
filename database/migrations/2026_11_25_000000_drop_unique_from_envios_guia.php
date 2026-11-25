<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            $indexes = DB::select("SHOW INDEX FROM envios WHERE Key_name = 'envios_guia_unique'");
            if (! empty($indexes)) {
                Schema::table('envios', function (Blueprint $table): void {
                    $table->dropUnique('envios_guia_unique');
                });
            }
        } else {
            Schema::table('envios', function (Blueprint $table): void {
                $table->dropUnique('envios_guia_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('envios', function (Blueprint $table): void {
            $table->unique('guia', 'envios_guia_unique');
        });
    }
};
