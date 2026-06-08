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

        if (!in_array($role, ['caissier', 'pharmacien', 'admin'])) {
            abort(403, 'Accès refusé.');
        }
    }

    /**
     * Vérifie que l'utilisateur peut opérer la caisse Accueil (vente de tickets).
     * Seuls le caissier et l'admin sont autorisés.
     */
    protected function ensureCanOperateAccueil()
    {
        $role = auth()->user()->role;

        if (!in_array($role, ['caissier', 'admin'])) {
            abort(403, 'Cette action est réservée au caissier d\'accueil et à l\'administrateur.');
        }
    }

    /**
     * Vérifie que l'utilisateur peut opérer la caisse Pharmacie (vente de médicaments).
     * Seuls le pharmacien et l'admin sont autorisés.
     */
    protected function ensureCanOperatePharmacie()
    {
        $role = auth()->user()->role;

        if (!in_array($role, ['pharmacien', 'admin'])) {
            abort(403, 'Cette action est réservée au pharmacien et à l\'administrateur.');
        }
    }

    /**
     * Vérifie que l'utilisateur peut consulter (tous les rôles authentifiés).
     */
    protected function ensureCanRead()
    {
        $role = auth()->user()->role;

        if (!in_array($role, ['caissier', 'superviseur', 'pharmacien', 'admin'])) {
            abort(403, 'Accès refusé.');
        }
    }

    /**
     * Vérifie que l'utilisateur peut consulter les rapports (superviseur ou admin).
     */
    protected function ensureCanReadReports()
    {
        if (!in_array(auth()->user()->role, ['superviseur', 'admin'])) {
            abort(403, 'Accès réservé au superviseur et à l\'administrateur.');
        }
    }

    /**
     * Vérifie que l'utilisateur est admin.
     */
    protected function ensureAdmin()
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Accès réservé aux administrateurs.');
        }
    }
    protected function ensureCanReadStock()
{
    $role = auth()->user()->role;

    if (!in_array($role, ['pharmacien', 'superviseur', 'admin'])) {
        abort(403, 'Accès refusé au stock pharmacie.');
    }
}

protected function ensureCanReceiveStock()
    {
        if (!in_array(auth()->user()->role, ['pharmacien', 'admin'])) {
            abort(403, 'Cette action est réservée au pharmacien et à l\'administrateur.');
        }
    }

    protected function ensureCanRegularizeStock()
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'La régularisation du stock est réservée à l\'administrateur.');
        }
    }

}
