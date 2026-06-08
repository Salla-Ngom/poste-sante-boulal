<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RapportController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Sécurité : seuls superviseur et admin
        if (!in_array($user->role, ['admin', 'superviseur'])) {
            return redirect()->route('dashboard')
                ->with('error', 'Accès refusé.');
        }

        $tenantId = $user->tenant_id;

        // Période : 30 derniers jours par défaut
        $dateDebut = $request->input('debut')
            ? Carbon::parse($request->input('debut'))->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();

        $dateFin = $request->input('fin')
            ? Carbon::parse($request->input('fin'))->endOfDay()
            : Carbon::now()->endOfDay();

        // === KPIs globaux ===
        $totalTickets = Ticket::where('tenant_id', $tenantId)
            ->whereBetween('emis_le', [$dateDebut, $dateFin])
            ->where('statut', 'actif')
            ->count();

        $totalRecettes = (float) Ticket::where('tenant_id', $tenantId)
            ->whereBetween('emis_le', [$dateDebut, $dateFin])
            ->where('statut', 'actif')
            ->sum('prix_paye');

        $ticketMoyen = $totalTickets > 0 ? $totalRecettes / $totalTickets : 0;

        // === Évolution journalière ===
        $evolutionJournaliere = Ticket::where('tenant_id', $tenantId)
            ->whereBetween('emis_le', [$dateDebut, $dateFin])
            ->where('statut', 'actif')
            ->select(
                DB::raw('DATE(date_emission) as date'),
                DB::raw('COUNT(*) as nombre_tickets'),
                DB::raw('SUM(prix_paye) as recettes')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // === Répartition par type de prestation ===
        $repartitionTypes = Ticket::where('tickets.tenant_id', $tenantId)
            ->whereBetween('emis_le', [$dateDebut, $dateFin])
            ->where('tickets.statut', 'actif')
            ->join('ticket_types', 'tickets.ticket_type_id', '=', 'ticket_types.id')
            ->select(
                'ticket_types.libelle',
                DB::raw('COUNT(*) as nombre'),
                DB::raw('SUM(tickets.prix_paye) as total')
            )
            ->groupBy('ticket_types.id', 'ticket_types.libelle')
            ->orderByDesc('nombre')
            ->get();

        // === Performance par agent ===
        $performanceAgents = Ticket::where('tickets.tenant_id', $tenantId)
            ->whereBetween('emis_le', [$dateDebut, $dateFin])
            ->where('tickets.statut', 'actif')
            ->join('users', 'tickets.user_id', '=', 'users.id')
            ->select(
                'users.name',
                DB::raw('COUNT(*) as nombre_tickets'),
                DB::raw('SUM(tickets.prix_paye) as total_recettes')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_recettes')
            ->get();

        // Note : Les tranches d'âge sont retirées car la saisie patient
        // est désormais supprimée à l'accueil. Elles seront réintroduites
        // pour les ventes pharmacie qui conservent les infos patient.

        return Inertia::render('Rapports/Index', [
            'kpis' => [
                'totalTickets' => $totalTickets,
                'totalRecettes' => $totalRecettes,
                'ticketMoyen' => round($ticketMoyen, 2),
            ],
            'evolutionJournaliere' => $evolutionJournaliere,
            'repartitionTypes' => $repartitionTypes,
            'performanceAgents' => $performanceAgents,
            'filtres' => [
                'debut' => $dateDebut->format('Y-m-d'),
                'fin' => $dateFin->format('Y-m-d'),
            ],
        ]);
    }
}
