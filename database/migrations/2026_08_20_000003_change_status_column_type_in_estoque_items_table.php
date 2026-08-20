<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE estoque_items MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'FALTA'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE estoque_items MODIFY COLUMN status ENUM('FALTA','SEPARADO','RETIRADO','FABRICA','FABRICAR INTERNO KANBAN','FECHADO') NOT NULL DEFAULT 'FALTA'");
    }
};
