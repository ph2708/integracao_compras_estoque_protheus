<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compras_items', function (Blueprint $table) {
            if (!Schema::hasColumn('compras_items', 'quantidade_adicional')) {
                $table->decimal('quantidade_adicional', 12, 4)->default(0.0000)->after('estoque_item_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('compras_items', function (Blueprint $table) {
            if (Schema::hasColumn('compras_items', 'quantidade_adicional')) {
                $table->dropColumn('quantidade_adicional');
            }
        });
    }
};
