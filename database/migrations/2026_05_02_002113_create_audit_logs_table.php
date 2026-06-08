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
    Schema::create('audit_logs', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->uuid('tenant_id');
        $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
        $table->string('action', 100);
        $table->string('entite', 100);
        $table->string('entite_id')->nullable();
        $table->json('details')->nullable();
        $table->ipAddress('ip')->nullable();
        $table->timestamp('survenu_le');

        $table->foreign('tenant_id')
              ->references('id')->on('tenants')
              ->onDelete('restrict');

        $table->index(['tenant_id', 'survenu_le']);
        $table->index(['entite', 'entite_id']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
