<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trabajo_profesor', function (Blueprint $table) {
            $table->boolean('terminos_aceptados')->default(false)->after('motivo_rechazo');
            $table->boolean('datos_aceptados')->default(false)->after('terminos_aceptados');
        });
    }

    public function down(): void
    {
        Schema::table('trabajo_profesor', function (Blueprint $table) {
            $table->dropColumn(['terminos_aceptados', 'datos_aceptados']);
        });
    }
};
