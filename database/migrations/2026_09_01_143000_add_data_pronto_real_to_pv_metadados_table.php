<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pv_metadados', function (Blueprint $table) {
            if (!Schema::hasColumn('pv_metadados', 'data_pronto_real')) {
                $table->string('data_pronto_real')->nullable()->after('data_liberacao_estoque');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pv_metadados', function (Blueprint $table) {
            if (Schema::hasColumn('pv_metadados', 'data_pronto_real')) {
                $table->dropColumn('data_pronto_real');
            }
        });
    }
};
