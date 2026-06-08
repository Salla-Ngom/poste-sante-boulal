<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesActions;
use App\Models\AuditLog;
use App\Models\Medicament;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StockController extends Controller
{
    use AuthorizesActions;

    /**
     * Liste des médicaments avec leur stock actuel
     */
    public function index(Request $request)
    {
        $this->ensureCanReadStock();

        $user = auth()->user();
        $tenantId = $user->tenant_id;

        $recherche = $request->input('recherche', '');
        $filtre = $request->input('filtre', 'tous');

        $query = Medicament::where('tenant_id', $tenantId)
            ->where('actif', true);

        if ($recherche) {
            $query->where('libelle', 'like', "%{$recherche}%");
        }

        if ($filtre === 'alerte') {
            $query->whereColumn('quantite_stock', '<=', 'seuil_alerte');
        } elseif ($filtre === 'rupture') {
            $query->where('quantite_stock', '<=', 0);
        } elseif ($filtre === 'ok') {
            $query->whereColumn('quantite_stock', '>', 'seuil_alerte');
        }

        $medicaments = $query->orderBy('libelle')->get();

        // Stats globales
        $stats = [
            'total' => Medicament::where('tenant_id', $tenantId)->where('actif', true)->count(),
            'alerte' => Medicament::where('tenant_id', $tenantId)
                ->where('actif', true)
                ->whereColumn('quantite_stock', '<=', 'seuil_alerte')
                ->where('quantite_stock', '>', 0)
                ->count(),
            'rupture' => Medicament::where('tenant_id', $tenantId)
                ->where('actif', true)
                ->where('quantite_stock', '<=', 0)
                ->count(),
            'valorisation' => (float) Medicament::where('tenant_id', $tenantId)
                ->where('actif', true)
                ->selectRaw('SUM(quantite_stock * prix) as total')
                ->value('total'),
        ];

        return Inertia::render('Stocks/Index', [
            'medicaments' => $medicaments,
            'stats' => $stats,
            'filtres' => [
                'recherche' => $recherche,
                'filtre' => $filtre,
            ],
        ]);
    }

    /**
     * Enregistre une entrée de stock (réception fournisseur).
     * Autorisé : pharmacien + admin
     */
    public function receptionner(Request $request)
    {
        $this->ensureCanReceiveStock();

        $user = auth()->user();

        $validated = $request->validate([
            'medicament_id' => 'required|exists:medicaments,id',
            'quantite' => 'required|integer|min:1',
            'reference_externe' => 'nullable|string|max:100',
            'motif' => 'nullable|string|max:500',
        ], [
            'medicament_id.required' => 'Le médicament est obligatoire.',
            'medicament_id.exists' => 'Médicament invalide.',
            'quantite.required' => 'La quantité est obligatoire.',
            'quantite.integer' => 'La quantité doit être un nombre entier.',
            'quantite.min' => 'La quantité doit être au moins de 1.',
        ]);

        $medicament = Medicament::where('id', $validated['medicament_id'])
            ->where('tenant_id', $user->tenant_id)
            ->firstOrFail();

        DB::transaction(function () use ($medicament, $user, $validated, $request) {
            $quantiteAvant = $medicament->quantite_stock;
            $quantiteApres = $quantiteAvant + $validated['quantite'];

            // Mise à jour du stock
            $medicament->update(['quantite_stock' => $quantiteApres]);

            // Enregistrement du mouvement
            $mouvement = StockMovement::create([
                'tenant_id' => $user->tenant_id,
                'medicament_id' => $medicament->id,
                'user_id' => $user->id,
                'type' => 'entree',
                'quantite' => $validated['quantite'],
                'quantite_avant' => $quantiteAvant,
                'quantite_apres' => $quantiteApres,
                'motif' => $validated['motif'] ?? 'Réception fournisseur',
                'reference_externe' => $validated['reference_externe'],
                'survenu_le' => Carbon::now(),
            ]);

            // Audit
            AuditLog::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'action' => 'stock.entree',
                'entite' => 'stock_movement',
                'entite_id' => $mouvement->id,
                'details' => [
                    'medicament' => $medicament->libelle,
                    'quantite' => $validated['quantite'],
                    'avant' => $quantiteAvant,
                    'apres' => $quantiteApres,
                    'reference' => $validated['reference_externe'] ?? null,
                ],
                'ip' => $request->ip(),
                'survenu_le' => Carbon::now(),
            ]);
        });

        return redirect()->route('stocks.index')
            ->with('success', 'Stock réceptionné avec succès. Nouvelle quantité : ' . ($medicament->quantite_stock + $validated['quantite']) . ' unités.');
    }

    /**
     * Régularise le stock (correction d'écart d'inventaire).
     * Autorisé : admin uniquement
     */
    public function regulariser(Request $request)
    {
        $this->ensureCanRegularizeStock();

        $user = auth()->user();

        $validated = $request->validate([
            'medicament_id' => 'required|exists:medicaments,id',
            'nouvelle_quantite' => 'required|integer|min:0',
            'motif' => 'required|string|min:5|max:500',
        ], [
            'medicament_id.required' => 'Le médicament est obligatoire.',
            'nouvelle_quantite.required' => 'La nouvelle quantité est obligatoire.',
            'nouvelle_quantite.integer' => 'La quantité doit être un nombre entier.',
            'nouvelle_quantite.min' => 'La quantité ne peut pas être négative.',
            'motif.required' => 'Le motif de régularisation est obligatoire.',
            'motif.min' => 'Le motif doit faire au moins 5 caractères (pour traçabilité).',
        ]);

        $medicament = Medicament::where('id', $validated['medicament_id'])
            ->where('tenant_id', $user->tenant_id)
            ->firstOrFail();

        $quantiteAvant = $medicament->quantite_stock;
        $nouvelle = $validated['nouvelle_quantite'];
        $difference = $nouvelle - $quantiteAvant;

        if ($difference === 0) {
            return back()->withErrors([
                'nouvelle_quantite' => 'La nouvelle quantité est identique à l\'actuelle. Aucune régularisation nécessaire.'
            ]);
        }

        DB::transaction(function () use ($medicament, $user, $validated, $request, $quantiteAvant, $nouvelle, $difference) {
            // Mise à jour du stock
            $medicament->update(['quantite_stock' => $nouvelle]);

            // Type selon le sens de la régularisation
            $type = $difference > 0 ? 'regularisation_positive' : 'regularisation_negative';

            // Enregistrement du mouvement
            $mouvement = StockMovement::create([
                'tenant_id' => $user->tenant_id,
                'medicament_id' => $medicament->id,
                'user_id' => $user->id,
                'type' => $type,
                'quantite' => abs($difference),
                'quantite_avant' => $quantiteAvant,
                'quantite_apres' => $nouvelle,
                'motif' => $validated['motif'],
                'survenu_le' => Carbon::now(),
            ]);

            // Audit
            AuditLog::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'action' => 'stock.regularisation',
                'entite' => 'stock_movement',
                'entite_id' => $mouvement->id,
                'details' => [
                    'medicament' => $medicament->libelle,
                    'avant' => $quantiteAvant,
                    'apres' => $nouvelle,
                    'difference' => $difference,
                    'motif' => $validated['motif'],
                ],
                'ip' => $request->ip(),
                'survenu_le' => Carbon::now(),
            ]);
        });

        $message = $difference > 0
            ? "Régularisation positive enregistrée. Stock ajusté de +{$difference} unités."
            : "Régularisation négative enregistrée. Stock ajusté de {$difference} unités.";

        return redirect()->route('stocks.index')->with('success', $message);
    }

    /**
     * Historique des mouvements de stock
     */
    public function mouvements(Request $request)
    {
        $this->ensureCanReadStock();

        $user = auth()->user();
        $tenantId = $user->tenant_id;

        // Filtres
        $dateDebut = $request->input('debut')
            ? Carbon::parse($request->input('debut'))->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();

        $dateFin = $request->input('fin')
            ? Carbon::parse($request->input('fin'))->endOfDay()
            : Carbon::now()->endOfDay();

        $query = StockMovement::with(['medicament', 'user'])
            ->where('tenant_id', $tenantId)
            ->whereBetween('survenu_le', [$dateDebut, $dateFin]);

        // Filtre par médicament
        if ($request->filled('medicament_id')) {
            $query->where('medicament_id', $request->input('medicament_id'));
        }

        // Filtre par type
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $mouvements = $query->orderByDesc('survenu_le')
            ->paginate(30)
            ->withQueryString();

        // Liste des médicaments pour le filtre
        $medicaments = Medicament::where('tenant_id', $tenantId)
            ->orderBy('libelle')
            ->get(['id', 'libelle']);

        return Inertia::render('Stocks/Mouvements', [
            'mouvements' => $mouvements,
            'medicaments' => $medicaments,
            'filtres' => [
                'debut' => $dateDebut->format('Y-m-d'),
                'fin' => $dateFin->format('Y-m-d'),
                'medicament_id' => $request->input('medicament_id', ''),
                'type' => $request->input('type', ''),
            ],
        ]);
    }
}
