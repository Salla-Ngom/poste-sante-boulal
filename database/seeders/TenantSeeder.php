<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::create([
            'id' => '00000000-0000-0000-0000-000000000001',
            'name' => 'Poste de Santé de Boulal',
            'code' => 'BOULAL',
        ]);
    }
}
