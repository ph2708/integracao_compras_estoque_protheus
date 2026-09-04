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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'permissao_compras_edicao')) {
                $table->boolean('permissao_compras_edicao')->default(false)->after('permissao_painel_montagem');
            }
            if (!Schema::hasColumn('users', 'permissao_estoque_edicao')) {
                $table->boolean('permissao_estoque_edicao')->default(false)->after('permissao_compras_edicao');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'permissao_compras_edicao')) {
                $table->dropColumn('permissao_compras_edicao');
            }
            if (Schema::hasColumn('users', 'permissao_estoque_edicao')) {
                $table->dropColumn('permissao_estoque_edicao');
            }
        });
    }
};
