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
        Schema::create('pharmacy_tickets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('tenant_id');
            $table->date('date_emission');
            $table->unsignedInteger('numero');
            $table->uuid('cash_session_id');
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->decimal('total', 10, 2);

            // Informations patient (obligatoire à la pharmacie pour traçabilité)
            $table->string('patient_nom', 100);
            $table->string('patient_prenom', 100);
            $table->unsignedSmallInteger('patient_age')->nullable();

            $table->enum('statut', ['actif', 'annule'])->default('actif');
            $table->timestamp('emis_le');
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')->on('tenants')
                ->onDelete('restrict');

            $table->foreign('cash_session_id')
                ->references('id')->on('cash_sessions')
                ->onDelete('restrict');

            $table->unique(['tenant_id', 'date_emission', 'numero']);
            $table->index(['tenant_id', 'date_emission']);
            $table->index(['cash_session_id', 'statut']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacy_tickets');
    }
};
