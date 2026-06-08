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
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('tenant_id')->after('id')->nullable();
            $table->enum('role', ['admin', 'superviseur', 'caissier', 'pharmacien'])
                  ->default('caissier')
                  ->after('password');
            $table->boolean('actif')->default(true)->after('role');

            $table->foreign('tenant_id')
                  ->references('id')->on('tenants')
                  ->onDelete('restrict');

            $table->index(['tenant_id', 'actif']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id', 'actif']);
            $table->dropColumn(['tenant_id', 'role', 'actif']);
        });
    }
};
