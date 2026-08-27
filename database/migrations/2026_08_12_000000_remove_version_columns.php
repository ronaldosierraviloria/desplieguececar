<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Elimina el sistema de versiones (V1, V2, V3) del proyecto.
     * Usa hasTable() + hasColumn() para ser idempotente y tolerar
     * entornos donde las tablas/columnas no existan.
     */
    public function up(): void
    {
        if (Schema::hasTable('trabajo') && Schema::hasColumn('trabajo', 'version_actual')) {
            Schema::table('trabajo', function (Blueprint $table) {
                $table->dropColumn('version_actual');
            });
        }

        if (Schema::hasTable('historial_estados') && Schema::hasColumn('historial_estados', 'version_documento')) {
            Schema::table('historial_estados', function (Blueprint $table) {
                $table->dropColumn('version_documento');
            });
        }

        if (Schema::hasTable('retroalimentaciones') && Schema::hasColumn('retroalimentaciones', 'version_documento')) {
            Schema::table('retroalimentaciones', function (Blueprint $table) {
                $table->dropColumn('version_documento');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('trabajo') && ! Schema::hasColumn('trabajo', 'version_actual')) {
            Schema::table('trabajo', function (Blueprint $table) {
                $table->string('version_actual', 50)->default('v1');
            });
        }

        if (Schema::hasTable('historial_estados') && ! Schema::hasColumn('historial_estados', 'version_documento')) {
            Schema::table('historial_estados', function (Blueprint $table) {
                $table->string('version_documento')->default('v1');
            });
        }

        if (Schema::hasTable('retroalimentaciones') && ! Schema::hasColumn('retroalimentaciones', 'version_documento')) {
            Schema::table('retroalimentaciones', function (Blueprint $table) {
                $table->string('version_documento')->default('v1');
            });
        }
    }
};
