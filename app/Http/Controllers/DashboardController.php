<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\Expense;
use App\Models\Medicament;
use App\Models\PharmacyTicket;
use App\Models\Ticket;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;
        $aujourdhui = Carbon::today();

        // ===== Dispatcher selon le rôle =====
        if ($user->role === 'pharmacien') {
            return $this->dashboardPharmacien($user, $tenantId, $aujourdhui);
        }

        if ($user->role === 'caissier') {
            return $this->dashboardCaissier($user, $tenantId, $aujourdhui);
        }

        // Admin et Superviseur ont la vue globale
        return $this->dashboardAdmin($user, $tenantId, $aujourdhui);
    }

    // ============================================================
    // === DASHBOARD CAISSIER (Accueil)
    // ============================================================
    private function dashboardCaissier($user, $tenantId, $aujourdhui)
    {
        // Session caisse Accueil ouverte
        $sessionOuverte = CashSession::where('user_id', $user->id)
            ->where('statut', 'ouverte')
            ->where('type_caisse', 'accueil')
            ->first();

        // Stats tickets du jour
        $ticketsAujourdhui = Ticket::where('tenant_id', $tenantId)
            ->where('date_emission', $aujourdhui)
            ->where('statut', 'actif')
            ->where('user_id', $user->id) // caissier voit que les siens
            ->count();

        $recettesAujourdhui = (float) Ticket::where('tenant_id', $tenantId)
            ->where('date_emission', $aujourdhui)
            ->where('statut', 'actif')
            ->where('user_id', $user->id)
            ->sum('prix_paye');

        // Dépenses du jour pour la session en cours
        $depensesAujourdhui = 0;
        if ($sessionOuverte) {
            $depensesAujourdhui = (float) Expense::where('cash_session_id', $sessionOuverte->id)
                ->sum('montant');
        }

        // Derniers tickets du caissier
        $derniersTickets = Ticket::with(['ticketType', 'user'])
            ->where('tenant_id', $tenantId)
            ->where('statut', 'actif')
            ->where('user_id', $user->id)
            ->orderBy('emis_le', 'desc')
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'sessionOuverte' => $sessionOuverte,
            'sessionPharmacieOuverte' => null,
            'derniersTickets' => $derniersTickets,
            'derniersTicketsPharmacie' => [],
            'stats' => [
                // KPIs caissier
                'ticketsAujourdhui' => $ticketsAujourdhui,
                'recettesAujourdhui' => $recettesAujourdhui,
                'depensesAujourdhui' => $depensesAujourdhui,
                // Champs pharmacie vides
                'ventesPharmacieAujourdhui' => 0,
                'recettesPharmacieAujourdhui' => 0,
                'medicamentsEnAlerte' => 0,
                'medicamentsEnRupture' => 0,
                // Semaine / mois vides pour caissier
                'ticketsSemaine' => 0,
                'recettesSemaine' => 0,
                'ticketsMois' => 0,
                'recettesMois' => 0,
            ],
        ]);
    }

    // ============================================================
    // === DASHBOARD PHARMACIEN
    // ============================================================
    private function dashboardPharmacien($user, $tenantId, $aujourdhui)
    {
        // Session caisse Pharmacie ouverte
        $sessionOuverte = CashSession::where('user_id', $user->id)
            ->where('statut', 'ouverte')
            ->where('type_caisse', 'pharmacie')
            ->first();

        // Stats ventes pharmacie du jour
        $ventesAujourdhui = PharmacyTicket::where('tenant_id', $tenantId)
            ->where('date_emission', $aujourdhui)
            ->where('statut', 'actif')
            ->where('user_id', $user->id)
            ->count();

        $recettesAujourdhui = (float) PharmacyTicket::where('tenant_id', $tenantId)
            ->where('date_emission', $aujourdhui)
            ->where('statut', 'actif')
            ->where('user_id', $user->id)
            ->sum('total');

        // Médicaments en alerte / rupture
        $medicamentsEnAlerte = Medicament::where('tenant_id', $tenantId)
            ->where('actif', true)
            ->where('quantite_stock', '>', 0)
            ->whereColumn('quantite_stock', '<=', 'seuil_alerte')
            ->count();

        $medicamentsEnRupture = Medicament::where('tenant_id', $tenantId)
            ->where('actif', true)
            ->where('quantite_stock', '<=', 0)
            ->count();

        // Dernières ventes pharmacie du pharmacien
        $derniersTicketsPharmacie = PharmacyTicket::with(['lines', 'user'])
            ->where('tenant_id', $tenantId)
            ->where('statut', 'actif')
            ->where('user_id', $user->id)
            ->orderBy('emis_le', 'desc')
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'sessionOuverte' => null,
            'sessionPharmacieOuverte' => $sessionOuverte,
            'derniersTickets' => [],
            'derniersTicketsPharmacie' => $derniersTicketsPharmacie,
            'stats' => [
                // KPIs pharmacien
                'ventesPharmacieAujourdhui' => $ventesAujourdhui,
                'recettesPharmacieAujourdhui' => $recettesAujourdhui,
                'medicamentsEnAlerte' => $medicamentsEnAlerte,
                'medicamentsEnRupture' => $medicamentsEnRupture,
                // Champs caissier vides
                'ticketsAujourdhui' => 0,
                'recettesAujourdhui' => 0,
                'depensesAujourdhui' => 0,
                // Semaine / mois vides pour pharmacien
                'ticketsSemaine' => 0,
                'recettesSemaine' => 0,
                'ticketsMois' => 0,
                'recettesMois' => 0,
            ],
        ]);
    }

    // ============================================================
    // === DASHBOARD ADMIN / SUPERVISEUR (vue globale)
    // ============================================================
    private function dashboardAdmin($user, $tenantId, $aujourdhui)
    {
        // Session caisse accueil (pour admin actif, peut être null)
        $sessionOuverte = CashSession::where('user_id', $user->id)
            ->where('statut', 'ouverte')
            ->where('type_caisse', 'accueil')
            ->first();

        $sessionPharmacieOuverte = CashSession::where('user_id', $user->id)
            ->where('statut', 'ouverte')
            ->where('type_caisse', 'pharmacie')
            ->first();

        // === Stats tickets accueil du jour (tous caissiers) ===
        $ticketsAujourdhui = Ticket::where('tenant_id', $tenantId)
            ->where('date_emission', $aujourdhui)
            ->where('statut', 'actif')
            ->count();

        $recettesAujourdhui = (float) Ticket::where('tenant_id', $tenantId)
            ->where('date_emission', $aujourdhui)
            ->where('statut', 'actif')
            ->sum('prix_paye');

        // === Stats ventes pharmacie du jour (tous pharmaciens) ===
        $ventesPharmacieAujourdhui = PharmacyTicket::where('tenant_id', $tenantId)
            ->where('date_emission', $aujourdhui)
            ->where('statut', 'actif')
            ->count();

        $recettesPharmacieAujourdhui = (float) PharmacyTicket::where('tenant_id', $tenantId)
            ->where('date_emission', $aujourdhui)
            ->where('statut', 'actif')
            ->sum('total');

        // Dépenses du jour toutes sessions
        $depensesAujourdhui = (float) Expense::where('tenant_id', $tenantId)
            ->whereDate('depense_le', $aujourdhui)
            ->sum('montant');

        // Stock : médicaments en alerte/rupture
        $medicamentsEnAlerte = Medicament::where('tenant_id', $tenantId)
            ->where('actif', true)
            ->where('quantite_stock', '>', 0)
            ->whereColumn('quantite_stock', '<=', 'seuil_alerte')
            ->count();

        $medicamentsEnRupture = Medicament::where('tenant_id', $tenantId)
            ->where('actif', true)
            ->where('quantite_stock', '<=', 0)
            ->count();

        // Derniers tickets accueil (tous)
        $derniersTickets = Ticket::with(['ticketType', 'user'])
            ->where('tenant_id', $tenantId)
            ->where('statut', 'actif')
            ->orderBy('emis_le', 'desc')
            ->limit(5)
            ->get();

        // Dernières ventes pharmacie (toutes)
        $derniersTicketsPharmacie = PharmacyTicket::with(['lines', 'user'])
            ->where('tenant_id', $tenantId)
            ->where('statut', 'actif')
            ->orderBy('emis_le', 'desc')
            ->limit(5)
            ->get();

        // Stats semaine et mois (accueil + pharmacie cumulés)
        $debutSemaine = Carbon::now()->startOfWeek();
        $debutMois = Carbon::now()->startOfMonth();

        $ticketsSemaine = Ticket::where('tenant_id', $tenantId)
            ->where('date_emission', '>=', $debutSemaine)
            ->where('statut', 'actif')
            ->count();
        $ventesPharmacieSemaine = PharmacyTicket::where('tenant_id', $tenantId)
            ->where('date_emission', '>=', $debutSemaine)
            ->where('statut', 'actif')
            ->count();

        $recettesSemaine = (float) Ticket::where('tenant_id', $tenantId)
            ->where('date_emission', '>=', $debutSemaine)
            ->where('statut', 'actif')
            ->sum('prix_paye');
        $recettesPharmacieSemaine = (float) PharmacyTicket::where('tenant_id', $tenantId)
            ->where('date_emission', '>=', $debutSemaine)
            ->where('statut', 'actif')
            ->sum('total');

        $ticketsMois = Ticket::where('tenant_id', $tenantId)
            ->where('date_emission', '>=', $debutMois)
            ->where('statut', 'actif')
            ->count();
        $ventesPharmacieMois = PharmacyTicket::where('tenant_id', $tenantId)
            ->where('date_emission', '>=', $debutMois)
            ->where('statut', 'actif')
            ->count();

        $recettesMois = (float) Ticket::where('tenant_id', $tenantId)
            ->where('date_emission', '>=', $debutMois)
            ->where('statut', 'actif')
            ->sum('prix_paye');
        $recettesPharmacieMois = (float) PharmacyTicket::where('tenant_id', $tenantId)
            ->where('date_emission', '>=', $debutMois)
            ->where('statut', 'actif')
            ->sum('total');

        return Inertia::render('Dashboard', [
            'sessionOuverte' => $sessionOuverte,
            'sessionPharmacieOuverte' => $sessionPharmacieOuverte,
            'derniersTickets' => $derniersTickets,
            'derniersTicketsPharmacie' => $derniersTicketsPharmacie,
            'stats' => [
                'ticketsAujourdhui' => $ticketsAujourdhui,
                'recettesAujourdhui' => $recettesAujourdhui,
                'depensesAujourdhui' => $depensesAujourdhui,
                'ventesPharmacieAujourdhui' => $ventesPharmacieAujourdhui,
                'recettesPharmacieAujourdhui' => $recettesPharmacieAujourdhui,
                'medicamentsEnAlerte' => $medicamentsEnAlerte,
                'medicamentsEnRupture' => $medicamentsEnRupture,
                'ticketsSemaine' => $ticketsSemaine + $ventesPharmacieSemaine,
                'recettesSemaine' => $recettesSemaine + $recettesPharmacieSemaine,
                'ticketsMois' => $ticketsMois + $ventesPharmacieMois,
                'recettesMois' => $recettesMois + $recettesPharmacieMois,
            ],
        ]);
    }
}
