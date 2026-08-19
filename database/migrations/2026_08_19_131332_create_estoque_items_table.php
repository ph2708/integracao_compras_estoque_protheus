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
        Schema::create('estoque_items', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_produto');
            $table->string('descricao')->nullable();
            $table->string('op')->nullable();
            $table->string('pedido')->nullable();
            $table->string('cliente_obs')->nullable(); // C2_OBS do Protheus (Nome do Cliente)
            $table->decimal('quantidade', 12, 2)->default(1); // Quantidade Requisitada da OP
            $table->decimal('quantidade_estoque', 12, 2)->default(0); // Quantidade disponível em estoque física
            $table->enum('status', [
                'FALTA',
                'SEPARADO',
                'RETIRADO',
                'FABRICA',
                'FABRICAR INTERNO KANBAN'
            ])->default('FALTA');
            $table->text('observacao_estoque')->nullable(); // Observação interna do Estoque
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estoque_items');
    }
};
