<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CreateSuperadmin extends Command
{
    /**
     * Usage : php artisan superadmin:create
     *
     * Crée un compte superadmin, ou promeut un compte existant.
     * Le rôle superadmin ne peut PAS être attribué depuis l'interface web :
     * cette commande est le seul moyen d'en créer un.
     */
    protected $signature = 'superadmin:create';

    protected $description = 'Créer ou promouvoir un compte superadmin (désactivation agents + reset mots de passe)';

    public function handle(): int
    {
        $this->info('=== Création d\'un compte SUPERADMIN ===');
        $this->newLine();

        $email = $this->ask('Email du compte');

        $existant = User::where('email', $email)->first();

        // ─── Cas 1 : le compte existe → promotion ─────────────────────
        if ($existant) {
            if ($existant->role === 'superadmin') {
                $this->warn("Ce compte est déjà superadmin. Rien à faire.");
                return self::SUCCESS;
            }

            $this->line("Compte trouvé : {$existant->name} (rôle actuel : {$existant->role})");

            if (!$this->confirm('Promouvoir ce compte en superadmin ?', true)) {
                $this->warn('Opération annulée.');
                return self::SUCCESS;
            }

            $ancienRole = $existant->role;
            $existant->update(['role' => 'superadmin', 'actif' => true]);

            AuditLog::create([
                'tenant_id' => $existant->tenant_id,
                'user_id' => $existant->id,
                'action' => 'user.promoted_superadmin',
                'entite' => 'user',
                'entite_id' => $existant->id,
                'details' => [
                    'email' => $existant->email,
                    'ancien_role' => $ancienRole,
                    'via' => 'artisan superadmin:create',
                ],
                'ip' => 'console',
                'survenu_le' => Carbon::now(),
            ]);

            $this->info("✔ {$existant->name} est maintenant SUPERADMIN.");
            return self::SUCCESS;
        }

        // ─── Cas 2 : création d'un nouveau compte ─────────────────────
        $name = $this->ask('Nom complet');
        $password = $this->secret('Mot de passe (8 caractères minimum)');
        $confirmation = $this->secret('Confirmer le mot de passe');

        if (strlen($password) < 8) {
            $this->error('Le mot de passe doit contenir au moins 8 caractères.');
            return self::FAILURE;
        }
        if ($password !== $confirmation) {
            $this->error('Les mots de passe ne correspondent pas.');
            return self::FAILURE;
        }

        // Rattacher au tenant existant (application mono-poste)
        $tenantId = \DB::table('tenants')->value('id');
        if (!$tenantId) {
            $this->error('Aucun tenant en base. Créez d\'abord le tenant du poste.');
            return self::FAILURE;
        }

        $superadmin = User::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'email' => $email,
            'password' => $password, // hashé automatiquement par le cast du modèle
            'role' => 'superadmin',
            'actif' => true,
            'email_verified_at' => Carbon::now(),
        ]);

        AuditLog::create([
            'tenant_id' => $tenantId,
            'user_id' => $superadmin->id,
            'action' => 'user.created_superadmin',
            'entite' => 'user',
            'entite_id' => $superadmin->id,
            'details' => [
                'name' => $name,
                'email' => $email,
                'via' => 'artisan superadmin:create',
            ],
            'ip' => 'console',
            'survenu_le' => Carbon::now(),
        ]);

        $this->info("✔ Superadmin créé : {$name} <{$email}>");
        return self::SUCCESS;
    }
}
