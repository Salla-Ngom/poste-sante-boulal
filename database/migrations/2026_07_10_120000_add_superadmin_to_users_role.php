<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ajoute le rôle 'superadmin' à l'enum users.role.
     * Seul un superadmin peut désactiver un agent ou réinitialiser un mot de passe.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('superadmin','admin','superviseur','caissier','pharmacien') NOT NULL DEFAULT 'caissier'");
    }

    public function down(): void
    {
        // Rétrograder les superadmins en admin avant de retirer la valeur de l'enum
        DB::table('users')->where('role', 'superadmin')->update(['role' => 'admin']);
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','superviseur','caissier','pharmacien') NOT NULL DEFAULT 'caissier'");
    }
};
