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
    Schema::create('expenses', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('tenant_id');
        $table->uuid('cash_session_id');
        $table->foreignId('user_id')->constrained()->onDelete('restrict');
        $table->string('libelle');
        $table->decimal('montant', 10, 2);
        $table->timestamp('depense_le');
        $table->timestamps();

        $table->foreign('tenant_id')
              ->references('id')->on('tenants')
              ->onDelete('restrict');

        $table->foreign('cash_session_id')
              ->references('id')->on('cash_sessions')
              ->onDelete('restrict');

        $table->index(['cash_session_id']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
