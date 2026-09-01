<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estoque_items', function (Blueprint $table) {
            if (!Schema::hasColumn('estoque_items', 'filial')) {
                $table->string('filial', 10)->nullable()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('estoque_items', function (Blueprint $table) {
            if (Schema::hasColumn('estoque_items', 'filial')) {
                $table->dropColumn('filial');
            }
        });
    }
};
