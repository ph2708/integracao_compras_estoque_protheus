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
        Schema::table('pv_metadados', function (Blueprint $table) {
            $table->decimal('valor_bruto', 15, 2)->nullable()->after('marca');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pv_metadados', function (Blueprint $table) {
            $table->dropColumn('valor_bruto');
        });
    }
};
