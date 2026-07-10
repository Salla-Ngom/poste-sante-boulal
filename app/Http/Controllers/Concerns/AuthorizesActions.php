<?php

namespace App\Http\Controllers\Concerns;

trait AuthorizesActions
{
    /**
     * Vérifie que l'utilisateur peut effectuer des opérations (vente, caisse, dépenses).
     * Bloque le superviseur qui est en lecture seule.
     */
    protected function ensureCanOperate()
    {
        $role = auth()->user()->role;

        if ($role === 'superviseur') {
            abort(403, 'Le superviseur ne peut pas effectuer cette opération. Accès en lecture seule uniquement.');
        }

        if (!in_array($role, ['caissier', 'pharmacien', 'admin', 'superadmin'])) {
            abort(403, 'Accès refusé.');
        }
    }

    /**
     * Vérifie que l'utilisateur peut opérer la caisse Accueil (vente de tickets).
     */
    protected function ensureCanOperateAccueil()
    {
        $role = auth()->user()->role;

        if (!in_array($role, ['caissier', 'admin', 'superadmin'])) {
            abort(403, 'Cette action est réservée au caissier d\'accueil et à l\'administrateur.');
        }
    }

    /**
     * Vérifie que l'utilisateur peut opérer la caisse Pharmacie (vente de médicaments).
     */
    protected function ensureCanOperatePharmacie()
    {
        $role = auth()->user()->role;

        if (!in_array($role, ['pharmacien', 'admin', 'superadmin'])) {
            abort(403, 'Cette action est réservée au pharmacien et à l\'administrateur.');
        }
    }

    /**
     * Vérifie que l'utilisateur peut consulter (tous les rôles authentifiés).
     */
    protected function ensureCanRead()
    {
        $role = auth()->user()->role;

        if (!in_array($role, ['caissier', 'superviseur', 'pharmacien', 'admin', 'superadmin'])) {
            abort(403, 'Accès refusé.');
        }
    }

    /**
     * Vérifie que l'utilisateur peut consulter les rapports.
     */
    protected function ensureCanReadReports()
    {
        if (!in_array(auth()->user()->role, ['superviseur', 'admin', 'superadmin'])) {
            abort(403, 'Accès réservé au superviseur et à l\'administrateur.');
        }
    }

    /**
     * Vérifie que l'utilisateur est admin (le superadmin hérite de tous les droits admin).
     */
    protected function ensureAdmin()
    {
        if (!in_array(auth()->user()->role, ['admin', 'superadmin'])) {
            abort(403, 'Accès réservé aux administrateurs.');
        }
    }

    /**
     * Vérifie que l'utilisateur est SUPERADMIN.
     * Réservé aux actions sensibles : désactivation d'agents,
     * réinitialisation de mots de passe.
     */
    protected function ensureSuperadmin()
    {
        if (auth()->user()->role !== 'superadmin') {
            abort(403, 'Cette action est réservée au superadmin.');
        }
    }

    protected function ensureCanReadStock()
    {
        $role = auth()->user()->role;

        if (!in_array($role, ['pharmacien', 'superviseur', 'admin', 'superadmin'])) {
            abort(403, 'Accès refusé au stock pharmacie.');
        }
    }

    protected function ensureCanReceiveStock()
    {
        if (!in_array(auth()->user()->role, ['pharmacien', 'admin', 'superadmin'])) {
            abort(403, 'Cette action est réservée au pharmacien et à l\'administrateur.');
        }
    }

    protected function ensureCanRegularizeStock()
    {
        if (!in_array(auth()->user()->role, ['admin', 'superadmin'])) {
            abort(403, 'La régularisation du stock est réservée à l\'administrateur.');
        }
    }
}
