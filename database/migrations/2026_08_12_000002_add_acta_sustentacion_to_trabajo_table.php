<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega el campo para el Acta de Sustentación del trabajo de grado.
     * Cuando el administrador sube este acta, el proceso del proyecto finaliza.
     */
    public function up(): void
    {
        Schema::table('trabajo', function (Blueprint $table) {
            $table->string('archivo_acta_sustentacion', 255)->nullable()->after('archivo_acta');
        });
    }

    public function down(): void
    {
        Schema::table('trabajo', function (Blueprint $table) {
            $table->dropColumn('archivo_acta_sustentacion');
        });
    }
};
