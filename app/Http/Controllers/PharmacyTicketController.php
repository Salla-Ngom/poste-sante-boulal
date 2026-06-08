<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesActions;
use App\Models\AuditLog;
use App\Models\CashSession;
use App\Models\Medicament;
use App\Models\PharmacyTicket;
use App\Models\PharmacyTicketLine;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PharmacyTicketController extends Controller
{
    use AuthorizesActions;

    /**
     * Affiche l'interface de vente pharmacie (panier multi-lignes)
     */
    public function create()
    {
        $this->ensureCanOperatePharmacie();
        $user = auth()->user();

        // Vérifie qu'une session Pharmacie est ouverte
        $session = CashSession::where('user_id', $user->id)
            ->where('statut', 'ouverte')
            ->where('type_caisse', 'pharmacie')
            ->first();

        if (!$session) {
            return redirect()->route('dashboard')
                ->with('error', 'Vous devez d\'abord ouvrir votre caisse Pharmacie avant de vendre des médicaments.');
        }

        // Liste des médicaments actifs avec stock > 0
        $medicaments = Medicament::where('tenant_id', $user->tenant_id)
            ->where('actif', true)
            ->orderBy('libelle')
            ->get(['id', 'libelle', 'forme_conditionnement', 'prix', 'quantite_stock']);

        return Inertia::render('Pharmacy/Vendre', [
            'session' => $session,
            'medicaments' => $medicaments,
        ]);
    }

    /**
     * Enregistre une vente de médicaments (multi-lignes)
     * Transaction atomique : ticket + lignes + décréments stock + audit
     */
    public function store(Request $request)
    {
        $this->ensureCanOperatePharmacie();
        $user = auth()->user();

        // Validation des données globales et du panier
        $validated = $request->validate([
            'patient_nom' => 'required|string|max:100',
            'patient_prenom' => 'required|string|max:100',
            'lignes' => 'required|array|min:1',
            'lignes.*.medicament_id' => 'required|exists:medicaments,id',
            'lignes.*.quantite' => 'required|integer|min:1',
        ], [
            'patient_nom.required' => 'Le nom du patient est obligatoire.',
            'patient_prenom.required' => 'Le prénom du patient est obligatoire.',
            'lignes.required' => 'Le panier ne peut pas être vide.',
            'lignes.min' => 'Vous devez ajouter au moins un médicament.',
            'lignes.*.medicament_id.required' => 'Médicament invalide.',
            'lignes.*.quantite.min' => 'La quantité doit être au moins de 1.',
        ]);

        // Vérifier la session Pharmacie ouverte
        $session = CashSession::where('user_id', $user->id)
            ->where('statut', 'ouverte')
            ->where('type_caisse', 'pharmacie')
            ->first();

        if (!$session) {
            return redirect()->route('dashboard')
                ->with('error', 'Aucune session Pharmacie ouverte.');
        }

        // === Vérification préliminaire du stock (avant transaction) ===
        $erreursStock = [];
        $medicamentsCharges = [];

        foreach ($validated['lignes'] as $index => $ligne) {
            $med = Medicament::where('id', $ligne['medicament_id'])
                ->where('tenant_id', $user->tenant_id)
                ->where('actif', true)
                ->first();

            if (!$med) {
                $erreursStock["lignes.{$index}.medicament_id"] = 'Médicament invalide ou désactivé.';
                continue;
            }

            if ($med->quantite_stock < $ligne['quantite']) {
                $erreursStock["lignes.{$index}.quantite"] = "Stock insuffisant pour {$med->libelle} (disponible : {$med->quantite_stock}).";
                continue;
            }

            $medicamentsCharges[$index] = $med;
        }

        if (!empty($erreursStock)) {
            return back()->withErrors($erreursStock);
        }

        // === Transaction atomique : tout réussit ou tout échoue ===
        $ticket = DB::transaction(function () use ($user, $session, $validated, $request, $medicamentsCharges) {
            $aujourdhui = Carbon::today();

            // Verrou pour numérotation atomique
            $dernierNumero = PharmacyTicket::where('tenant_id', $user->tenant_id)
                ->where('date_emission', $aujourdhui)
                ->lockForUpdate()
                ->max('numero');

            $nouveauNumero = ($dernierNumero ?? 0) + 1;

            // Calcul du total
            $total = 0;
            foreach ($validated['lignes'] as $index => $ligne) {
                $med = $medicamentsCharges[$index];
                $total += $med->prix * $ligne['quantite'];
            }

            // Création du ticket pharmacie
            $ticket = PharmacyTicket::create([
                'tenant_id' => $user->tenant_id,
                'cash_session_id' => $session->id,
                'user_id' => $user->id,
                'numero' => $nouveauNumero,
                'date_emission' => $aujourdhui,
                'emis_le' => Carbon::now(),
                'total' => $total,
                'patient_nom' => $validated['patient_nom'],
                'patient_prenom' => $validated['patient_prenom'],
                'patient_age' => null, // Pas demandé selon décision client
                'statut' => 'actif',
            ]);

            // Pour chaque ligne : créer la ligne + décrémenter le stock + créer le mouvement
            foreach ($validated['lignes'] as $index => $ligne) {
                $med = $medicamentsCharges[$index];
                $quantiteAvant = $med->quantite_stock;
                $quantiteApres = $quantiteAvant - $ligne['quantite'];
                $sousTotal = $med->prix * $ligne['quantite'];

                // Création de la ligne (dénormalisée : libellé + prix copiés)
                PharmacyTicketLine::create([
                    'pharmacy_ticket_id' => $ticket->id,
                    'medicament_id' => $med->id,
                    'libelle_medicament' => $med->libelle,
                    'quantite' => $ligne['quantite'],
                    'prix_unitaire' => $med->prix,
                    'sous_total' => $sousTotal,
                ]);

                // Décrément du stock
                $med->update(['quantite_stock' => $quantiteApres]);

                // Mouvement de stock (sortie pour vente)
                StockMovement::create([
                    'tenant_id' => $user->tenant_id,
                    'medicament_id' => $med->id,
                    'user_id' => $user->id,
                    'type' => 'sortie_vente',
                    'quantite' => $ligne['quantite'],
                    'quantite_avant' => $quantiteAvant,
                    'quantite_apres' => $quantiteApres,
                    'motif' => 'Vente pharmacie ticket N°' . str_pad($nouveauNumero, 4, '0', STR_PAD_LEFT),
                    'pharmacy_ticket_id' => $ticket->id,
                    'survenu_le' => Carbon::now(),
                ]);
            }

            // Audit
            AuditLog::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'action' => 'pharmacy_ticket.sold',
                'entite' => 'pharmacy_ticket',
                'entite_id' => $ticket->id,
                'details' => [
                    'numero' => $nouveauNumero,
                    'patient' => $validated['patient_prenom'] . ' ' . $validated['patient_nom'],
                    'total' => $total,
                    'nb_lignes' => count($validated['lignes']),
                ],
                'ip' => $request->ip(),
                'survenu_le' => Carbon::now(),
            ]);

            return $ticket;
        });

        return redirect()->route('pharmacy.recu', $ticket->id);
    }

    /**
     * Affiche le reçu imprimable d'un ticket pharmacie
     */
    public function recu($id)
    {
        $user = auth()->user();

        $ticket = PharmacyTicket::with(['lines', 'user', 'tenant'])
            ->where('id', $id)
            ->where('tenant_id', $user->tenant_id)
            ->firstOrFail();

        return Inertia::render('Pharmacy/Recu', [
            'ticket' => $ticket,
        ]);
    }

    /**
     * Historique des ventes pharmacie
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        // Accès : pharmacien (ses ventes), superviseur (toutes), admin (toutes)
        if (!in_array($user->role, ['pharmacien', 'superviseur', 'admin'])) {
            abort(403, 'Accès refusé.');
        }

        $dateDebut = $request->input('debut')
            ? Carbon::parse($request->input('debut'))->startOfDay()
            : Carbon::today();

        $dateFin = $request->input('fin')
            ? Carbon::parse($request->input('fin'))->endOfDay()
            : Carbon::today()->endOfDay();

        $query = PharmacyTicket::with(['lines', 'user'])
            ->where('tenant_id', $tenantId)
            ->whereBetween('emis_le', [$dateDebut, $dateFin]);

        // Pharmacien ne voit que ses propres ventes
        if ($user->role === 'pharmacien') {
            $query->where('user_id', $user->id);
        }

        // Recherche par nom patient
        if ($request->filled('recherche')) {
            $recherche = $request->input('recherche');
            $query->where(function ($q) use ($recherche) {
                $q->where('patient_nom', 'like', "%{$recherche}%")
                  ->orWhere('patient_prenom', 'like', "%{$recherche}%");
            });
        }

        $statsQuery = clone $query;
        $totalTickets = (clone $statsQuery)->count();
        $totalRecettes = (float) (clone $statsQuery)->sum('total');

        $tickets = $query->orderBy('emis_le', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Pharmacy/Historique', [
            'tickets' => $tickets,
            'stats' => [
                'totalTickets' => $totalTickets,
                'totalRecettes' => $totalRecettes,
            ],
            'filtres' => [
                'debut' => $dateDebut->format('Y-m-d'),
                'fin' => $dateFin->format('Y-m-d'),
                'recherche' => $request->input('recherche', ''),
            ],
        ]);
    }
}
