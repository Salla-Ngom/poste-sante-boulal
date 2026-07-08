<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class MedicamentStatsController extends Controller
{
    /**
     * Statistiques des ventes de médicaments sur une période.
     *
     * Pour chaque médicament :
     * - quantite_vendue : total des unités vendues (SUM quantite)
     * - nombre_ventes   : nombre de tickets contenant ce médicament
     * - chiffre_affaires: somme des sous-totaux
     * - stock_actuel    : stock restant (jointure medicaments)
     *
     * Basé sur pharmacy_ticket_lines qui conserve le libellé en
     * snapshot — les stats restent exactes même si un médicament
     * est renommé ou désactivé ensuite.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Accès : admin, superviseur et pharmacien
        if (!in_array($user->role, ['admin', 'superviseur', 'pharmacien'])) {
            abort(403);
        }

        // Période par défaut : le mois en cours
        $debut = $request->input('debut')
            ? Carbon::parse($request->input('debut'))->startOfDay()
            : Carbon::now()->startOfMonth();

        $fin = $request->input('fin')
            ? Carbon::parse($request->input('fin'))->endOfDay()
            : Carbon::now()->endOfDay();

        // Garde-fou : si l'utilisateur inverse les dates
        if ($debut->greaterThan($fin)) {
            [$debut, $fin] = [$fin->copy()->startOfDay(), $debut->copy()->endOfDay()];
        }

        $stats = DB::table('pharmacy_ticket_lines as l')
            ->join('pharmacy_tickets as t', 't.id', '=', 'l.pharmacy_ticket_id')
            ->leftJoin('medicaments as m', 'm.id', '=', 'l.medicament_id')
            ->where('t.tenant_id', $user->tenant_id)
            ->where('t.statut', 'actif')
            ->whereBetween('t.date_emission', [
                $debut->toDateString(),
                $fin->toDateString(),
            ])
            ->groupBy('l.medicament_id', 'l.libelle_medicament')
            ->select(
                'l.medicament_id',
                'l.libelle_medicament',
                DB::raw('SUM(l.quantite) as quantite_vendue'),
                DB::raw('COUNT(DISTINCT l.pharmacy_ticket_id) as nombre_ventes'),
                DB::raw('SUM(l.sous_total) as chiffre_affaires'),
                DB::raw('MAX(m.quantite_stock) as stock_actuel'),
                DB::raw('MAX(m.seuil_alerte) as seuil_alerte'),
                DB::raw('MAX(m.forme_conditionnement) as forme')
            )
            ->orderByDesc(DB::raw('SUM(l.quantite)'))
            ->get();

        // Nombre total de tickets pharmacie sur la période
        $nombreTickets = DB::table('pharmacy_tickets')
            ->where('tenant_id', $user->tenant_id)
            ->where('statut', 'actif')
            ->whereBetween('date_emission', [
                $debut->toDateString(),
                $fin->toDateString(),
            ])
            ->count();

        return Inertia::render('Medicaments/Stats', [
            'stats' => $stats,
            'totaux' => [
                'unitesVendues' => (int) $stats->sum('quantite_vendue'),
                'chiffreAffaires' => (float) $stats->sum('chiffre_affaires'),
                'medicamentsDistincts' => $stats->count(),
                'nombreTickets' => $nombreTickets,
            ],
            'filtres' => [
                'debut' => $debut->format('Y-m-d'),
                'fin' => $fin->format('Y-m-d'),
            ],
        ]);
    }
}
