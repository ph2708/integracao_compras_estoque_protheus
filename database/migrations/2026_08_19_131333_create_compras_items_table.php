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
        Schema::create('compras_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estoque_item_id')->constrained('estoque_items')->onDelete('cascade');
            $table->string('pedido_compra')->nullable();
            $table->string('codigo_fornecedor')->nullable();
            $table->decimal('valor_unitario', 14, 2)->nullable();
            $table->decimal('ipi', 8, 2)->nullable();
            $table->date('data_pc')->nullable();
            $table->date('data_pagamento')->nullable();
            $table->decimal('frete', 14, 2)->nullable();
            $table->string('solicitacao_compra')->nullable();
            $table->decimal('valor_total', 14, 2)->nullable();
            $table->string('condicao_pagamento')->nullable();
            $table->enum('status_pagamento', [
                'PENDENTE',
                'PAGAMENTO ANTECIPADO',
                'FATURADO',
                'PAGO'
            ])->default('PENDENTE');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras_items');
    }
};
