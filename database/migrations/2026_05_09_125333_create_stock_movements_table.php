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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('tenant_id');
            $table->uuid('medicament_id');
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->enum('type', ['entree', 'sortie_vente', 'regularisation_positive', 'regularisation_negative']);
            $table->integer('quantite'); // toujours positif, le sens est dans le type
            $table->integer('quantite_avant');
            $table->integer('quantite_apres');
            $table->string('motif', 500)->nullable();
            $table->string('reference_externe', 100)->nullable(); // n° bon de livraison, etc.
            $table->unsignedBigInteger('pharmacy_ticket_id')->nullable(); // lien si sortie liée à une vente
            $table->timestamp('survenu_le');
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')->on('tenants')
                ->onDelete('restrict');

            $table->foreign('medicament_id')
                ->references('id')->on('medicaments')
                ->onDelete('restrict');

            $table->foreign('pharmacy_ticket_id')
                ->references('id')->on('pharmacy_tickets')
                ->onDelete('set null');

            $table->index(['tenant_id', 'medicament_id']);
            $table->index(['tenant_id', 'survenu_le']);
            $table->index('pharmacy_ticket_id');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
