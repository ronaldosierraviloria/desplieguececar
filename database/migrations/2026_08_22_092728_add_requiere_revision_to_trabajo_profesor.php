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
        Schema::table('trabajo_profesor', function (Blueprint $table) {
            $table->boolean('requiere_nueva_revision')->default(false)->after('estado_revision');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trabajo_profesor', function (Blueprint $table) {
            $table->dropColumn('requiere_nueva_revision');
        });
    }
};
