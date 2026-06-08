<?php

namespace Database\Seeders;

use App\Models\TicketType;
use Illuminate\Database\Seeder;

class TicketTypeSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = '00000000-0000-0000-0000-000000000001';

        $types = [
            ['libelle' => 'Consultation adulte',       'prix' => 1000],
            ['libelle' => 'Consultation enfant',       'prix' => 500],
            ['libelle' => 'Consultation prénatale',    'prix' => 1500],
            ['libelle' => 'Accouchement',              'prix' => 5000],
            ['libelle' => 'Pansement simple',          'prix' => 500],
            ['libelle' => 'Pansement complexe',        'prix' => 1500],
            ['libelle' => 'Injection',                 'prix' => 500],
            ['libelle' => 'Vaccination',               'prix' => 0],
            ['libelle' => 'Carnet de santé',           'prix' => 200],
            ['libelle' => 'Certificat médical',        'prix' => 2000],
        ];

        foreach ($types as $type) {
            TicketType::create([
                'tenant_id' => $tenantId,
                'libelle' => $type['libelle'],
                'prix' => $type['prix'],
                'actif' => true,
            ]);
        }
    }
}
