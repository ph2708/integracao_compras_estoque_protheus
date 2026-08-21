<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE compras_items MODIFY COLUMN status_pagamento VARCHAR(50) NOT NULL DEFAULT 'PENDENTE'");
        DB::statement("UPDATE compras_items SET status_pagamento = 'PA' WHERE status_pagamento IN ('PAG. ANTECIPADO', 'PAGAMENTO ANTECIPADO', 'ANTECIPADO')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE compras_items MODIFY COLUMN status_pagamento ENUM('PENDENTE','PAGAMENTO ANTECIPADO','FATURADO','PAGO','PA') NOT NULL DEFAULT 'PENDENTE'");
    }
};
