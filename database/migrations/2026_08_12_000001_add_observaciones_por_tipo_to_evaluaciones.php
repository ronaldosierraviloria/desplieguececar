<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluaciones', function (Blueprint $table) {
            // JSON con observaciones separadas por tipo:
            // {"propuesta_de_grado": "...", "trabajo_de_grado": "...", "pasantia": "..."}
            $table->json('observaciones_por_tipo')->nullable()->after('observaciones_globales');
        });

        // Migrar datos existentes: el valor actual de observaciones_globales
        // se asigna al tipo actual de cada fila
        $evaluaciones = DB::table('evaluaciones')->get();
        foreach ($evaluaciones as $eval) {
            if (!empty($eval->observaciones_globales) && !empty($eval->tipo_plantilla)) {
                $obs = [$eval->tipo_plantilla => $eval->observaciones_globales];
                DB::table('evaluaciones')
                    ->where('id', $eval->id)
                    ->update(['observaciones_por_tipo' => json_encode($obs)]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('evaluaciones', function (Blueprint $table) {
            $table->dropColumn('observaciones_por_tipo');
        });
    }
};
