<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estudiante', function (Blueprint $table) {
            $table->text('motivo_eliminacion')->nullable()->after('id_area');
        });
    }

    public function down(): void
    {
        Schema::table('estudiante', function (Blueprint $table) {
            $table->dropColumn('motivo_eliminacion');
        });
    }
};
