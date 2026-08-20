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
        Schema::table('estoque_items', function (Blueprint $table) {
            if (!Schema::hasColumn('estoque_items', 'fechada_em')) {
                $table->timestamp('fechada_em')->nullable()->after('observacao_estoque');
            }
            if (!Schema::hasColumn('estoque_items', 'fechada_por')) {
                $table->unsignedBigInteger('fechada_por')->nullable()->after('fechada_em');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estoque_items', function (Blueprint $table) {
            if (Schema::hasColumn('estoque_items', 'fechada_em')) {
                $table->dropColumn('fechada_em');
            }
            if (Schema::hasColumn('estoque_items', 'fechada_por')) {
                $table->dropColumn('fechada_por');
            }
        });
    }
};
