<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Prise en charge CMU sur les ventes pharmacie.
     * Une vente CMU est gratuite pour le patient : elle compte dans les
     * ventes (valeur, stock, statistiques) mais PAS dans les espèces
     * attendues en caisse à la clôture.
     */
    public function up(): void
    {
        Schema::table('pharmacy_tickets', function (Blueprint $table) {
            $table->boolean('est_cmu')->default(false)->after('patient_age');
        });
    }

    public function down(): void
    {
        Schema::table('pharmacy_tickets', function (Blueprint $table) {
            $table->dropColumn('est_cmu');
        });
    }
};
