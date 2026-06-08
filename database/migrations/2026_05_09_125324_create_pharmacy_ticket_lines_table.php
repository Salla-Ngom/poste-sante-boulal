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
        Schema::create('pharmacy_ticket_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('pharmacy_ticket_id');
            $table->uuid('medicament_id');
            $table->string('libelle_medicament', 200); // dénormalisé
            $table->integer('quantite');
            $table->decimal('prix_unitaire', 10, 2); // dénormalisé
            $table->decimal('sous_total', 10, 2);
            $table->timestamps();

            $table->foreign('pharmacy_ticket_id')
                ->references('id')->on('pharmacy_tickets')
                ->onDelete('cascade');

            $table->foreign('medicament_id')
                ->references('id')->on('medicaments')
                ->onDelete('restrict');

            $table->index('pharmacy_ticket_id');
            $table->index('medicament_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacy_ticket_lines');
    }
};
