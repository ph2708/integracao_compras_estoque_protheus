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
        Schema::table('pv_metadados', function (Blueprint $table) {
            $table->integer('qtd')->default(1)->after('marca');
            $table->string('time_prod')->nullable()->after('qtd');
            $table->string('data_emissao')->nullable()->after('time_prod');
            $table->string('data_contratual')->nullable()->after('data_emissao');
            $table->string('data_pa_pg')->nullable()->after('data_contratual');
            $table->string('data_pronto')->nullable()->after('data_pa_pg');
            $table->string('data_boom')->nullable()->after('data_pronto');
            $table->string('data_liberacao_estoque')->nullable()->after('data_boom');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pv_metadados', function (Blueprint $table) {
            $table->dropColumn([
                'qtd',
                'time_prod',
                'data_emissao',
                'data_contratual',
                'data_pa_pg',
                'data_pronto',
                'data_boom',
                'data_liberacao_estoque',
            ]);
        });
    }
};
