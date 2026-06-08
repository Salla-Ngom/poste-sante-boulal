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
    Schema::create('cancellations', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->unsignedBigInteger('ticket_id');
        $table->foreignId('user_id')->constrained()->onDelete('restrict');
        $table->string('motif');
        $table->timestamp('annule_le');
        $table->timestamps();

        $table->foreign('ticket_id')
              ->references('id')->on('tickets')
              ->onDelete('restrict');

        $table->unique('ticket_id');
        $table->index('annule_le');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cancellations');
    }
};
