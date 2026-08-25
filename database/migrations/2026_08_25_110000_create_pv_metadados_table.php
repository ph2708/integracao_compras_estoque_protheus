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
        Schema::create('pv_metadados', function (Blueprint $table) {
            $table->id();
            $table->string('pedido', 50)->unique();
            $table->string('info')->nullable();
            $table->string('status_pv', 50)->nullable();
            $table->string('fabrica', 50)->nullable();
            $table->string('marca', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pv_metadados');
    }
};
