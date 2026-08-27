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
        if (!Schema::hasColumn('estoque_items', 'updated_by')) {
            Schema::table('estoque_items', function (Blueprint $table) {
                $table->string('updated_by')->nullable()->after('observacao_estoque');
            });
        }

        if (!Schema::hasColumn('compras_items', 'updated_by')) {
            Schema::table('compras_items', function (Blueprint $table) {
                $table->string('updated_by')->nullable()->after('status_pagamento');
            });
        }

        if (!Schema::hasColumn('pv_metadados', 'updated_by')) {
            Schema::table('pv_metadados', function (Blueprint $table) {
                $table->string('updated_by')->nullable()->after('data_liberacao_estoque');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('estoque_items', 'updated_by')) {
            Schema::table('estoque_items', function (Blueprint $table) {
                $table->dropColumn('updated_by');
            });
        }

        if (Schema::hasColumn('compras_items', 'updated_by')) {
            Schema::table('compras_items', function (Blueprint $table) {
                $table->dropColumn('updated_by');
            });
        }

        if (Schema::hasColumn('pv_metadados', 'updated_by')) {
            Schema::table('pv_metadados', function (Blueprint $table) {
                $table->dropColumn('updated_by');
            });
        }
    }
};
