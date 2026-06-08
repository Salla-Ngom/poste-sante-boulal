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
    Schema::create('cash_reports', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('cash_session_id');
        $table->string('contenu_hash', 64);
        $table->string('pdf_path');
        $table->timestamp('genere_le');
        $table->timestamps();

        $table->foreign('cash_session_id')
              ->references('id')->on('cash_sessions')
              ->onDelete('restrict');

        $table->unique('cash_session_id');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_reports');
    }
};
