<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trabajo', function (Blueprint $table) {
            $table->string('codigo_proyecto', 30)->nullable()->unique()->after('id_trabajo');
            $table->string('archivo_acta', 255)->nullable()->after('archivo_pdf');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trabajo', function (Blueprint $table) {
            $table->dropColumn(['codigo_proyecto', 'archivo_acta']);
        });
    }
};
