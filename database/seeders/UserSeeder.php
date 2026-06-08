<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = '00000000-0000-0000-0000-000000000001';

        User::create([
            'tenant_id' => $tenantId,
            'name' => 'Salla NGOM',
            'email' => 'admin@boulal.test',
            'password' => 'password',
            'role' => 'admin',
            'actif' => true,
            'email_verified_at' => now(),
        ]);

        User::create([
            'tenant_id' => $tenantId,
            'name' => 'Aminata DIOP',
            'email' => 'caissier@boulal.test',
            'password' => 'password',
            'role' => 'caissier',
            'actif' => true,
            'email_verified_at' => now(),
        ]);

        User::create([
            'tenant_id' => $tenantId,
            'name' => 'Moussa FALL',
            'email' => 'superviseur@boulal.test',
            'password' => 'password',
            'role' => 'superviseur',
            'actif' => true,
            'email_verified_at' => now(),
        ]);

        User::create([
            'tenant_id' => $tenantId,
            'name' => 'Mariama BA',
            'email' => 'pharmacien@boulal.test',
            'password' => 'password',
            'role' => 'pharmacien',
            'actif' => true,
            'email_verified_at' => now(),
        ]);
    }
}
