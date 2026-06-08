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
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->enum('type_caisse', ['accueil', 'pharmacie'])->default('accueil');
            $table->foreignId('user_id')->constrained()->onDelete('restrict');

            // FIX : ->nullable() pour empêcher MySQL d'ajouter automatiquement
            // ON UPDATE CURRENT_TIMESTAMP sur la première colonne TIMESTAMP.
            // Sans cela, ouverte_le se met à jour à chaque modification de la ligne.
            $table->timestamp('ouverte_le')->nullable();
            $table->timestamp('fermee_le')->nullable();

            $table->decimal('fond_caisse_initial', 10, 2)->default(0);
            $table->decimal('montant_compte', 10, 2)->nullable();
            $table->decimal('ecart', 10, 2)->nullable();
            $table->enum('statut', ['ouverte', 'fermee'])->default('ouverte');
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')->on('tenants')
                ->onDelete('restrict');

            $table->index(['tenant_id', 'statut']);
            $table->index(['user_id', 'statut']);
            $table->index(['tenant_id', 'type_caisse', 'statut']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_sessions');
    }
};
