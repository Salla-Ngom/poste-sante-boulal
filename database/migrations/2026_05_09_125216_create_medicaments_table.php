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
        Schema::create('medicaments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('libelle', 200);
            $table->string('forme_conditionnement', 200)->nullable();
            $table->decimal('prix', 10, 2);
            $table->integer('quantite_stock')->default(0);
            $table->integer('seuil_alerte')->default(10);
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')->on('tenants')
                ->onDelete('restrict');

            $table->index(['tenant_id', 'actif']);
            $table->index(['tenant_id', 'libelle']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicaments');
    }
};
