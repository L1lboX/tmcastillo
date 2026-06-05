<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isSqlite = DB::getDriverName() === 'sqlite';

        if ($isSqlite) {
            $this->upSqlite();
        } else {
            $this->upMysql();
        }
    }

    public function down(): void
    {
        $isSqlite = DB::getDriverName() === 'sqlite';

        if ($isSqlite) {
            $this->downSqlite();
        } else {
            $this->downMysql();
        }
    }

    private function upMysql(): void
    {
        Schema::table('envios', function (Blueprint $table): void {
            $table->dropForeign(['cliente_dni']);
            $table->dropIndex(['cliente_dni']);
        });

        Schema::table('cuentas_corrientes', function (Blueprint $table): void {
            $table->dropForeign(['cliente_dni']);
            $table->dropIndex(['cliente_dni', 'fecha']);
        });

        DB::statement('ALTER TABLE clientes DROP PRIMARY KEY');
        DB::statement('ALTER TABLE clientes ADD COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST');
        DB::statement('ALTER TABLE clientes ADD COLUMN tipo_documento VARCHAR(3) NULL AFTER dni');
        DB::statement('ALTER TABLE clientes MODIFY dni VARCHAR(12) NULL');
        DB::statement('ALTER TABLE clientes ADD UNIQUE INDEX clientes_dni_unique (dni)');

        DB::statement('ALTER TABLE envios ADD COLUMN cliente_id BIGINT UNSIGNED NULL AFTER cliente_dni');
        DB::statement('UPDATE envios e INNER JOIN clientes c ON c.dni = e.cliente_dni SET e.cliente_id = c.id');
        DB::statement('ALTER TABLE envios DROP COLUMN cliente_dni');

        Schema::table('envios', function (Blueprint $table): void {
            $table->unsignedBigInteger('cliente_id')->nullable(false)->change();
            $table->foreign('cliente_id')->references('id')->on('clientes')->restrictOnDelete();
            $table->index('cliente_id');
        });

        DB::statement('ALTER TABLE cuentas_corrientes ADD COLUMN cliente_id BIGINT UNSIGNED NULL AFTER cliente_dni');
        DB::statement('UPDATE cuentas_corrientes cc INNER JOIN clientes c ON c.dni = cc.cliente_dni SET cc.cliente_id = c.id');
        DB::statement('ALTER TABLE cuentas_corrientes DROP COLUMN cliente_dni');

        Schema::table('cuentas_corrientes', function (Blueprint $table): void {
            $table->unsignedBigInteger('cliente_id')->nullable(false)->change();
            $table->foreign('cliente_id')->references('id')->on('clientes')->cascadeOnDelete();
            $table->index(['cliente_id', 'fecha']);
        });
    }

    private function downMysql(): void
    {
        Schema::table('cuentas_corrientes', function (Blueprint $table): void {
            $table->dropForeign(['cliente_id']);
            $table->dropIndex(['cliente_id', 'fecha']);
        });

        DB::statement('ALTER TABLE cuentas_corrientes ADD COLUMN cliente_dni VARCHAR(12) NULL AFTER cliente_id');
        DB::statement('UPDATE cuentas_corrientes cc INNER JOIN clientes c ON c.id = cc.cliente_id SET cc.cliente_dni = c.dni');
        DB::statement('ALTER TABLE cuentas_corrientes DROP COLUMN cliente_id');
        DB::statement('ALTER TABLE cuentas_corrientes MODIFY cliente_dni VARCHAR(12) NOT NULL');

        Schema::table('cuentas_corrientes', function (Blueprint $table): void {
            $table->foreign('cliente_dni')->references('dni')->on('clientes')->cascadeOnDelete();
            $table->index(['cliente_dni', 'fecha']);
        });

        Schema::table('envios', function (Blueprint $table): void {
            $table->dropForeign(['cliente_id']);
            $table->dropIndex(['cliente_id']);
        });

        DB::statement('ALTER TABLE envios ADD COLUMN cliente_dni VARCHAR(12) NULL AFTER cliente_id');
        DB::statement('UPDATE envios e INNER JOIN clientes c ON c.id = e.cliente_id SET e.cliente_dni = c.dni');
        DB::statement('ALTER TABLE envios DROP COLUMN cliente_id');
        DB::statement('ALTER TABLE envios MODIFY cliente_dni VARCHAR(12) NOT NULL');

        Schema::table('envios', function (Blueprint $table): void {
            $table->foreign('cliente_dni')->references('dni')->on('clientes')->restrictOnDelete()->cascadeOnUpdate();
            $table->index('cliente_dni');
        });

        DB::statement('ALTER TABLE clientes DROP INDEX clientes_dni_unique');
        DB::statement('ALTER TABLE clientes DROP COLUMN tipo_documento');
        DB::statement('ALTER TABLE clientes DROP PRIMARY KEY, DROP COLUMN id');
        DB::statement('ALTER TABLE clientes MODIFY dni VARCHAR(12) NOT NULL, ADD PRIMARY KEY (dni)');
    }

    private function upSqlite(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('clientes_new', function (Blueprint $table): void {
            $table->id();
            $table->string('dni', 12)->nullable()->unique();
            $table->string('tipo_documento', 3)->nullable();
            $table->string('nombre', 120);
            $table->string('telefono', 20)->nullable();
            $table->string('direccion', 180)->nullable();
            $table->timestamps();
        });

        DB::statement('INSERT INTO clientes_new (id, dni, nombre, telefono, direccion, created_at, updated_at) SELECT rowid, dni, nombre, telefono, direccion, created_at, updated_at FROM clientes');
        DB::statement('DROP TABLE clientes');
        DB::statement('ALTER TABLE clientes_new RENAME TO clientes');

        Schema::create('envios_new', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->date('fecha');
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('transportista_id')->nullable();
            $table->unsignedInteger('cantidad');
            $table->string('tipo', 80);
            $table->unsignedBigInteger('tipo_paquete_id')->nullable();
            $table->string('especificacion_tamano', 40)->nullable();
            $table->string('especificacion_peso', 40)->nullable();
            $table->text('detalle');
            $table->string('guia', 40)->nullable()->unique();
            $table->string('pago', 20);
            $table->decimal('monto', 10, 2)->nullable();
            $table->decimal('costo_transportista', 10, 2)->nullable();
            $table->decimal('margen', 10, 2)->nullable();
            $table->timestamp('liquidado_at')->nullable();
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->foreign('cliente_id')->references('id')->on('clientes')->restrictOnDelete();
            $table->foreign('transportista_id')->references('id')->on('transportistas')->nullOnDelete();
            $table->foreign('tipo_paquete_id')->references('id')->on('tipo_paquetes')->nullOnDelete();
            $table->index('fecha');
            $table->index('pago');
            $table->index('cliente_id');
        });

        DB::statement('INSERT INTO envios_new (id, codigo, fecha, cliente_id, transportista_id, cantidad, tipo, tipo_paquete_id, especificacion_tamano, especificacion_peso, detalle, guia, pago, monto, costo_transportista, margen, liquidado_at, observacion, created_at, updated_at) SELECT e.id, e.codigo, e.fecha, COALESCE(c.id, 0), e.transportista_id, e.cantidad, e.tipo, e.tipo_paquete_id, e.especificacion_tamano, e.especificacion_peso, e.detalle, e.guia, e.pago, e.monto, e.costo_transportista, e.margen, e.liquidado_at, e.observacion, e.created_at, e.updated_at FROM envios e INNER JOIN clientes c ON c.dni = e.cliente_dni');
        DB::statement('DROP TABLE envios');
        DB::statement('ALTER TABLE envios_new RENAME TO envios');

        Schema::create('cuentas_corrientes_new', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('envio_id')->nullable();
            $table->string('tipo');
            $table->decimal('monto', 10, 2);
            $table->decimal('saldo_acumulado', 10, 2)->default(0);
            $table->date('fecha');
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->foreign('cliente_id')->references('id')->on('clientes')->cascadeOnDelete();
            $table->foreign('envio_id')->references('id')->on('envios')->nullOnDelete();
            $table->index(['cliente_id', 'fecha']);
            $table->index('tipo');
        });

        DB::statement('INSERT INTO cuentas_corrientes_new (id, cliente_id, envio_id, tipo, monto, saldo_acumulado, fecha, observacion, created_at, updated_at) SELECT cc.id, COALESCE(c.id, 0), cc.envio_id, cc.tipo, cc.monto, cc.saldo_acumulado, cc.fecha, cc.observacion, cc.created_at, cc.updated_at FROM cuentas_corrientes cc INNER JOIN clientes c ON c.dni = cc.cliente_dni');
        DB::statement('DROP TABLE cuentas_corrientes');
        DB::statement('ALTER TABLE cuentas_corrientes_new RENAME TO cuentas_corrientes');

        Schema::enableForeignKeyConstraints();
    }

    private function downSqlite(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('cuentas_corrientes_old', function (Blueprint $table): void {
            $table->id();
            $table->string('cliente_dni', 12);
            $table->unsignedBigInteger('envio_id')->nullable();
            $table->string('tipo');
            $table->decimal('monto', 10, 2);
            $table->decimal('saldo_acumulado', 10, 2)->default(0);
            $table->date('fecha');
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->foreign('cliente_dni')->references('dni')->on('clientes')->cascadeOnDelete();
            $table->foreign('envio_id')->references('id')->on('envios')->nullOnDelete();
            $table->index(['cliente_dni', 'fecha']);
            $table->index('tipo');
        });

        DB::statement('INSERT INTO cuentas_corrientes_old (id, cliente_dni, envio_id, tipo, monto, saldo_acumulado, fecha, observacion, created_at, updated_at) SELECT cc.id, c.dni, cc.envio_id, cc.tipo, cc.monto, cc.saldo_acumulado, cc.fecha, cc.observacion, cc.created_at, cc.updated_at FROM cuentas_corrientes cc INNER JOIN clientes c ON c.id = cc.cliente_id');
        DB::statement('DROP TABLE cuentas_corrientes');
        DB::statement('ALTER TABLE cuentas_corrientes_old RENAME TO cuentas_corrientes');

        Schema::create('envios_old', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->date('fecha');
            $table->string('cliente_dni', 12);
            $table->unsignedBigInteger('transportista_id')->nullable();
            $table->unsignedInteger('cantidad');
            $table->string('tipo', 80);
            $table->unsignedBigInteger('tipo_paquete_id')->nullable();
            $table->string('especificacion_tamano', 40)->nullable();
            $table->string('especificacion_peso', 40)->nullable();
            $table->text('detalle');
            $table->string('guia', 40)->nullable()->unique();
            $table->string('pago', 20);
            $table->decimal('monto', 10, 2)->nullable();
            $table->decimal('costo_transportista', 10, 2)->nullable();
            $table->decimal('margen', 10, 2)->nullable();
            $table->timestamp('liquidado_at')->nullable();
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->foreign('cliente_dni')->references('dni')->on('clientes')->restrictOnDelete();
            $table->foreign('transportista_id')->references('id')->on('transportistas')->nullOnDelete();
            $table->foreign('tipo_paquete_id')->references('id')->on('tipo_paquetes')->nullOnDelete();
            $table->index('fecha');
            $table->index('pago');
            $table->index('cliente_dni');
        });

        DB::statement('INSERT INTO envios_old (id, codigo, fecha, cliente_dni, transportista_id, cantidad, tipo, tipo_paquete_id, especificacion_tamano, especificacion_peso, detalle, guia, pago, monto, costo_transportista, margen, liquidado_at, observacion, created_at, updated_at) SELECT e.id, e.codigo, e.fecha, c.dni, e.transportista_id, e.cantidad, e.tipo, e.tipo_paquete_id, e.especificacion_tamano, e.especificacion_peso, e.detalle, e.guia, e.pago, e.monto, e.costo_transportista, e.margen, e.liquidado_at, e.observacion, e.created_at, e.updated_at FROM envios e INNER JOIN clientes c ON c.id = e.cliente_id');
        DB::statement('DROP TABLE envios');
        DB::statement('ALTER TABLE envios_old RENAME TO envios');

        Schema::create('clientes_old', function (Blueprint $table): void {
            $table->string('dni', 12)->primary();
            $table->string('nombre', 120);
            $table->string('telefono', 20)->nullable();
            $table->string('direccion', 180)->nullable();
            $table->timestamps();
        });

        DB::statement('INSERT INTO clientes_old (dni, nombre, telefono, direccion, created_at, updated_at) SELECT COALESCE(dni, CAST(id AS TEXT)), nombre, telefono, direccion, created_at, updated_at FROM clientes');
        DB::statement('DROP TABLE clientes');
        DB::statement('ALTER TABLE clientes_old RENAME TO clientes');

        Schema::enableForeignKeyConstraints();
    }
};
