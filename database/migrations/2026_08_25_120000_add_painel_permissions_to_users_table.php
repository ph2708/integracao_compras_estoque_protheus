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
            $table->boolean('permissao_painel_pcp')->default(true)->after('permissao_fechamento_op');
            $table->boolean('permissao_painel_pcp_edicao')->default(true)->after('permissao_painel_pcp');
            $table->boolean('permissao_painel_montagem')->default(true)->after('permissao_painel_pcp_edicao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'permissao_painel_pcp',
                'permissao_painel_pcp_edicao',
                'permissao_painel_montagem',
            ]);
        });
    }
};
