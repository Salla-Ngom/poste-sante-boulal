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

class MedicamentController extends Controller
{
    use AuthorizesActions;

    /**
     * Liste des médicaments avec stats
     */
    public function index(Request $request)
    {
        $this->ensureAdmin();

        $user = auth()->user();
        $tenantId = $user->tenant_id;

        // Filtres
        $recherche = $request->input('recherche', '');
        $filtre = $request->input('filtre', 'tous'); // tous, actifs, alerte, rupture

        $query = Medicament::where('tenant_id', $tenantId);

        // Recherche par libellé
        if ($recherche) {
            $query->where('libelle', 'like', "%{$recherche}%");
        }

        // Filtre par statut
        if ($filtre === 'actifs') {
            $query->where('actif', true);
        } elseif ($filtre === 'alerte') {
            $query->whereColumn('quantite_stock', '<=', 'seuil_alerte')
                  ->where('actif', true);
        } elseif ($filtre === 'rupture') {
            $query->where('quantite_stock', '<=', 0)
                  ->where('actif', true);
        }

        $medicaments = $query
            ->orderByDesc('actif')
            ->orderBy('libelle')
            ->get();

        // Stats globales
        $stats = [
            'total' => Medicament::where('tenant_id', $tenantId)->count(),
            'actifs' => Medicament::where('tenant_id', $tenantId)->where('actif', true)->count(),
            'alerte' => Medicament::where('tenant_id', $tenantId)
                ->where('actif', true)
                ->whereColumn('quantite_stock', '<=', 'seuil_alerte')
                ->count(),
            'rupture' => Medicament::where('tenant_id', $tenantId)
                ->where('actif', true)
                ->where('quantite_stock', '<=', 0)
                ->count(),
        ];

        return Inertia::render('Medicaments/Index', [
            'medicaments' => $medicaments,
            'stats' => $stats,
            'filtres' => [
                'recherche' => $recherche,
                'filtre' => $filtre,
            ],
        ]);
    }

    /**
     * Affiche le formulaire de création
     */
    public function create()
    {
        $this->ensureAdmin();

        return Inertia::render('Medicaments/Form', [
            'medicament' => null,
        ]);
    }

    /**
     * Enregistre un nouveau médicament
     */
    public function store(Request $request)
    {
        $this->ensureAdmin();

        $user = auth()->user();

        $validated = $request->validate([
            'libelle' => 'required|string|max:200',
            'forme_conditionnement' => 'nullable|string|max:200',
            'prix' => 'required|numeric|min:0',
            'quantite_stock' => 'required|integer|min:0',
            'seuil_alerte' => 'required|integer|min:0',
            'actif' => 'required|boolean',
        ], [
            'libelle.required' => 'Le libellé est obligatoire.',
            'prix.required' => 'Le prix est obligatoire.',
            'prix.min' => 'Le prix ne peut pas être négatif.',
            'quantite_stock.required' => 'La quantité initiale est obligatoire.',
            'quantite_stock.min' => 'La quantité ne peut pas être négative.',
            'seuil_alerte.required' => 'Le seuil d\'alerte est obligatoire.',
            'seuil_alerte.min' => 'Le seuil ne peut pas être négatif.',
        ]);

        // Vérifier l'unicité du libellé pour le tenant
        $existant = Medicament::where('tenant_id', $user->tenant_id)
            ->where('libelle', $validated['libelle'])
            ->first();

        if ($existant) {
            return back()->withErrors(['libelle' => 'Un médicament avec ce libellé existe déjà.']);
        }

        DB::transaction(function () use ($user, $validated, $request) {
            // Création du médicament
            $medicament = Medicament::create([
                'tenant_id' => $user->tenant_id,
                'libelle' => $validated['libelle'],
                'forme_conditionnement' => $validated['forme_conditionnement'],
                'prix' => $validated['prix'],
                'quantite_stock' => $validated['quantite_stock'],
                'seuil_alerte' => $validated['seuil_alerte'],
                'actif' => $validated['actif'],
            ]);

            // Si stock initial > 0, on enregistre un mouvement d'entrée
            if ($validated['quantite_stock'] > 0) {
                StockMovement::create([
                    'tenant_id' => $user->tenant_id,
                    'medicament_id' => $medicament->id,
                    'user_id' => $user->id,
                    'type' => 'entree',
                    'quantite' => $validated['quantite_stock'],
                    'quantite_avant' => 0,
                    'quantite_apres' => $validated['quantite_stock'],
                    'motif' => 'Stock initial à la création du médicament',
                    'survenu_le' => Carbon::now(),
                ]);
            }

            // Audit
            AuditLog::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'action' => 'medicament.created',
                'entite' => 'medicament',
                'entite_id' => $medicament->id,
                'details' => [
                    'libelle' => $medicament->libelle,
                    'prix' => (float) $medicament->prix,
                    'stock_initial' => $medicament->quantite_stock,
                    'seuil_alerte' => $medicament->seuil_alerte,
                ],
                'ip' => $request->ip(),
                'survenu_le' => Carbon::now(),
            ]);
        });

        return redirect()->route('medicaments.index')
            ->with('success', 'Médicament créé avec succès.');
    }

    /**
     * Affiche le formulaire d'édition
     */
    public function edit($id)
    {
        $this->ensureAdmin();

        $user = auth()->user();

        $medicament = Medicament::where('id', $id)
            ->where('tenant_id', $user->tenant_id)
            ->firstOrFail();

        return Inertia::render('Medicaments/Form', [
            'medicament' => $medicament,
        ]);
    }

    /**
     * Met à jour un médicament existant
     * NOTE : on ne modifie PAS la quantité de stock ici. Pour ça, on utilise
     * la page de réception de stock ou la régularisation (Jour 4).
     */
    public function update(Request $request, $id)
    {
        $this->ensureAdmin();

        $user = auth()->user();

        $medicament = Medicament::where('id', $id)
            ->where('tenant_id', $user->tenant_id)
            ->firstOrFail();

        $validated = $request->validate([
            'libelle' => 'required|string|max:200',
            'forme_conditionnement' => 'nullable|string|max:200',
            'prix' => 'required|numeric|min:0',
            'seuil_alerte' => 'required|integer|min:0',
            'actif' => 'required|boolean',
        ], [
            'libelle.required' => 'Le libellé est obligatoire.',
            'prix.required' => 'Le prix est obligatoire.',
            'seuil_alerte.required' => 'Le seuil d\'alerte est obligatoire.',
        ]);

        // Vérifier l'unicité du libellé (sauf pour le médicament courant)
        $existant = Medicament::where('tenant_id', $user->tenant_id)
            ->where('libelle', $validated['libelle'])
            ->where('id', '!=', $id)
            ->first();

        if ($existant) {
            return back()->withErrors(['libelle' => 'Un autre médicament avec ce libellé existe déjà.']);
        }

        $anciennes = [
            'libelle' => $medicament->libelle,
            'prix' => (float) $medicament->prix,
            'seuil_alerte' => $medicament->seuil_alerte,
            'actif' => $medicament->actif,
        ];

        DB::transaction(function () use ($medicament, $user, $validated, $request, $anciennes) {
            $medicament->update([
                'libelle' => $validated['libelle'],
                'forme_conditionnement' => $validated['forme_conditionnement'],
                'prix' => $validated['prix'],
                'seuil_alerte' => $validated['seuil_alerte'],
                'actif' => $validated['actif'],
            ]);

            AuditLog::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'action' => 'medicament.updated',
                'entite' => 'medicament',
                'entite_id' => $medicament->id,
                'details' => [
                    'avant' => $anciennes,
                    'apres' => [
                        'libelle' => $validated['libelle'],
                        'prix' => (float) $validated['prix'],
                        'seuil_alerte' => $validated['seuil_alerte'],
                        'actif' => $validated['actif'],
                    ],
                ],
                'ip' => $request->ip(),
                'survenu_le' => Carbon::now(),
            ]);
        });

        return redirect()->route('medicaments.index')
            ->with('success', 'Médicament modifié avec succès.');
    }

    /**
     * Active ou désactive un médicament (toggle rapide)
     */
    public function toggleActif(Request $request, $id)
    {
        $this->ensureAdmin();

        $user = auth()->user();

        $medicament = Medicament::where('id', $id)
            ->where('tenant_id', $user->tenant_id)
            ->firstOrFail();

        $ancienEtat = $medicament->actif;

        DB::transaction(function () use ($medicament, $user, $ancienEtat, $request) {
            $medicament->update(['actif' => !$medicament->actif]);

            AuditLog::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'action' => $medicament->actif ? 'medicament.activated' : 'medicament.deactivated',
                'entite' => 'medicament',
                'entite_id' => $medicament->id,
                'details' => [
                    'libelle' => $medicament->libelle,
                    'avant' => $ancienEtat,
                    'apres' => $medicament->actif,
                ],
                'ip' => $request->ip(),
                'survenu_le' => Carbon::now(),
            ]);
        });

        $message = $medicament->actif
            ? 'Médicament réactivé.'
            : 'Médicament désactivé. Il n\'apparaîtra plus dans la vente.';

        return redirect()->back()->with('success', $message);
    }
}
