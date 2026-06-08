<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesActions;
use App\Models\AuditLog;
use App\Models\CashSession;
use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TicketController extends Controller
{
    use AuthorizesActions;

    /**
     * Affiche le formulaire de vente de ticket
     * Réservé caissier d'accueil + admin (pas le pharmacien)
     */
    public function create()
    {
        $this->ensureCanOperateAccueil();
        $user = auth()->user();

        // Vérifie qu'une session de caisse Accueil est ouverte
        $session = CashSession::where('user_id', $user->id)
            ->where('statut', 'ouverte')
            ->where('type_caisse', 'accueil')
            ->first();

        if (!$session) {
            return redirect()->route('dashboard')
                ->with('error', 'Vous devez d\'abord ouvrir votre caisse avant de vendre des tickets.');
        }

        // Récupère les types de tickets actifs du tenant
        $ticketTypes = TicketType::where('tenant_id', $user->tenant_id)
            ->where('actif', true)
            ->orderBy('libelle')
            ->get();

        return Inertia::render('Tickets/Vendre', [
            'session' => $session,
            'ticketTypes' => $ticketTypes,
        ]);
    }

    /**
     * Enregistre une vente de ticket avec numérotation atomique
     * (Saisie patient supprimée à l'accueil — uniquement présente à la pharmacie)
     */
    public function store(Request $request)
    {
        $this->ensureCanOperateAccueil();
        $user = auth()->user();

        // Validation simplifiée : uniquement type + prix
        $validated = $request->validate([
            'ticket_type_id' => 'required|exists:ticket_types,id',
            'prix_paye' => 'required|numeric|min:0',
        ], [
            'ticket_type_id.required' => 'Veuillez sélectionner un type de prestation.',
            'ticket_type_id.exists' => 'Cette prestation n\'existe pas.',
            'prix_paye.required' => 'Le montant est obligatoire.',
            'prix_paye.numeric' => 'Le montant doit être un nombre.',
            'prix_paye.min' => 'Le montant ne peut pas être négatif.',
        ]);

        // Vérifie que le type appartient bien au tenant et est actif
        $ticketType = TicketType::where('id', $validated['ticket_type_id'])
            ->where('tenant_id', $user->tenant_id)
            ->where('actif', true)
            ->first();

        if (!$ticketType) {
            return back()->withErrors(['ticket_type_id' => 'Type de ticket invalide.']);
        }

        // Vérifie qu'une session Accueil est bien ouverte
        $session = CashSession::where('user_id', $user->id)
            ->where('statut', 'ouverte')
            ->where('type_caisse', 'accueil')
            ->first();

        if (!$session) {
            return redirect()->route('dashboard')
                ->with('error', 'Aucune session de caisse Accueil ouverte.');
        }

        // === Transaction atomique avec verrou pour la numérotation ===
        $ticket = DB::transaction(function () use ($user, $session, $ticketType, $validated, $request) {
            $aujourdhui = Carbon::today();

            // VERROU : on bloque les transactions concurrentes pour ce tenant + cette date
            $dernierNumero = Ticket::where('tenant_id', $user->tenant_id)
                ->where('date_emission', $aujourdhui)
                ->lockForUpdate()
                ->max('numero');

            $nouveauNumero = ($dernierNumero ?? 0) + 1;

            // Création du ticket SANS infos patient (saisie supprimée à l'accueil)
            $ticket = Ticket::create([
                'tenant_id' => $user->tenant_id,
                'date_emission' => $aujourdhui,
                'numero' => $nouveauNumero,
                'cash_session_id' => $session->id,
                'ticket_type_id' => $ticketType->id,
                'user_id' => $user->id,
                'prix_paye' => $validated['prix_paye'],
                'statut' => 'actif',
                'emis_le' => Carbon::now(),
            ]);

            // Journal d'audit
            AuditLog::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'action' => 'ticket.sold',
                'entite' => 'ticket',
                'entite_id' => $ticket->id,
                'details' => [
                    'numero' => $nouveauNumero,
                    'type' => $ticketType->libelle,
                    'prix_officiel' => (float) $ticketType->prix,
                    'prix_paye' => (float) $validated['prix_paye'],
                ],
                'ip' => $request->ip(),
                'survenu_le' => Carbon::now(),
            ]);

            return $ticket;
        });

        // Redirection vers le reçu pour impression
        return redirect()->route('tickets.recu', $ticket->id);
    }

    /**
     * Affiche le reçu imprimable d'un ticket
     */
    public function recu($id)
    {
        $user = auth()->user();

        $ticket = Ticket::with(['ticketType', 'user', 'tenant'])
            ->where('id', $id)
            ->where('tenant_id', $user->tenant_id)
            ->firstOrFail();

        return Inertia::render('Tickets/Recu', [
            'ticket' => $ticket,
        ]);
    }

    /**
     * Liste les tickets avec filtres
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        // Filtres par défaut : aujourd'hui
        $dateDebut = $request->input('debut')
            ? Carbon::parse($request->input('debut'))->startOfDay()
            : Carbon::today();

        $dateFin = $request->input('fin')
            ? Carbon::parse($request->input('fin'))->endOfDay()
            : Carbon::today()->endOfDay();

        // Construction de la requête
        $query = Ticket::with(['ticketType', 'user'])
            ->where('tenant_id', $tenantId)
            ->whereBetween('emis_le', [$dateDebut, $dateFin]);

        // Caissier ne voit que ses propres tickets
        if ($user->role === 'caissier') {
            $query->where('user_id', $user->id);
        } elseif ($request->filled('user_id')) {
            // Superviseur/admin peuvent filtrer par caissier
            $query->where('user_id', $request->input('user_id'));
        }

        // Filtre par type de prestation
        if ($request->filled('ticket_type_id')) {
            $query->where('ticket_type_id', $request->input('ticket_type_id'));
        }

        // Statistiques de la sélection courante (avant pagination)
        $statsQuery = clone $query;
        $totalFiltres = (clone $statsQuery)->count();
        $recettesFiltrees = (float) (clone $statsQuery)->sum('prix_paye');

        // Pagination (20 par page)
        $tickets = $query->orderBy('emis_le', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Listes pour les filtres
        $ticketTypes = TicketType::where('tenant_id', $tenantId)
            ->where('actif', true)
            ->orderBy('libelle')
            ->get(['id', 'libelle']);

        $caissiers = [];
        if (in_array($user->role, ['admin', 'superviseur'])) {
            $caissiers = \App\Models\User::where('tenant_id', $tenantId)
                ->where('actif', true)
                ->whereIn('role', ['caissier', 'admin'])
                ->orderBy('name')
                ->get(['id', 'name', 'role']);
        }

        return Inertia::render('Tickets/Index', [
            'tickets' => $tickets,
            'ticketTypes' => $ticketTypes,
            'caissiers' => $caissiers,
            'stats' => [
                'totalFiltres' => $totalFiltres,
                'recettesFiltrees' => $recettesFiltrees,
            ],
            'filtres' => [
                'debut' => $dateDebut->format('Y-m-d'),
                'fin' => $dateFin->format('Y-m-d'),
                'ticket_type_id' => $request->input('ticket_type_id', ''),
                'user_id' => $request->input('user_id', ''),
            ],
        ]);
    }
}
