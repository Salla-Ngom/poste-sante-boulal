<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TicketTypeController extends Controller
{
    /**
     * Vérifie que l'utilisateur est admin
     */
    private function ensureAdmin()
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Accès réservé aux administrateurs.');
        }
    }

    /**
     * Liste des types de tickets avec statistiques
     */
    public function index()
    {
        $this->ensureAdmin();

        $user = auth()->user();
        $tenantId = $user->tenant_id;

        // Récupération avec compteurs de ventes
        $ticketTypes = TicketType::where('tenant_id', $tenantId)
            ->withCount(['tickets as total_ventes' => function ($q) {
                $q->where('statut', 'actif');
            }])
            ->orderByDesc('actif')
            ->orderBy('libelle')
            ->get();

        return Inertia::render('TicketTypes/Index', [
            'ticketTypes' => $ticketTypes,
        ]);
    }

    /**
     * Affiche le formulaire de création
     */
    public function create()
    {
        $this->ensureAdmin();

        return Inertia::render('TicketTypes/Form', [
            'ticketType' => null,
        ]);
    }

    /**
     * Enregistre un nouveau type
     */
    public function store(Request $request)
    {
        $this->ensureAdmin();

        $user = auth()->user();

        $validated = $request->validate([
            'libelle' => 'required|string|max:100',
            'prix' => 'required|numeric|min:0',
            'actif' => 'required|boolean',
        ], [
            'libelle.required' => 'Le libellé est obligatoire.',
            'libelle.max' => 'Le libellé ne doit pas dépasser 100 caractères.',
            'prix.required' => 'Le prix est obligatoire.',
            'prix.numeric' => 'Le prix doit être un nombre.',
            'prix.min' => 'Le prix ne peut pas être négatif.',
        ]);

        // Vérifier qu'un type avec le même libellé n'existe pas déjà
        $existant = TicketType::where('tenant_id', $user->tenant_id)
            ->where('libelle', $validated['libelle'])
            ->first();

        if ($existant) {
            return back()->withErrors(['libelle' => 'Un type avec ce libellé existe déjà.']);
        }

        DB::transaction(function () use ($user, $validated, $request) {
            $type = TicketType::create([
                'tenant_id' => $user->tenant_id,
                'libelle' => $validated['libelle'],
                'prix' => $validated['prix'],
                'actif' => $validated['actif'],
            ]);

            AuditLog::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'action' => 'ticket_type.created',
                'entite' => 'ticket_type',
                'entite_id' => $type->id,
                'details' => [
                    'libelle' => $type->libelle,
                    'prix' => (float) $type->prix,
                    'actif' => $type->actif,
                ],
                'ip' => $request->ip(),
                'survenu_le' => Carbon::now(),
            ]);
        });

        return redirect()->route('ticket-types.index')
            ->with('success', 'Type de prestation créé avec succès.');
    }

    /**
     * Affiche le formulaire d'édition
     */
    public function edit($id)
    {
        $this->ensureAdmin();

        $user = auth()->user();

        $ticketType = TicketType::where('id', $id)
            ->where('tenant_id', $user->tenant_id)
            ->firstOrFail();

        return Inertia::render('TicketTypes/Form', [
            'ticketType' => $ticketType,
        ]);
    }

    /**
     * Met à jour un type existant
     */
    public function update(Request $request, $id)
    {
        $this->ensureAdmin();

        $user = auth()->user();

        $ticketType = TicketType::where('id', $id)
            ->where('tenant_id', $user->tenant_id)
            ->firstOrFail();

        $validated = $request->validate([
            'libelle' => 'required|string|max:100',
            'prix' => 'required|numeric|min:0',
            'actif' => 'required|boolean',
        ], [
            'libelle.required' => 'Le libellé est obligatoire.',
            'libelle.max' => 'Le libellé ne doit pas dépasser 100 caractères.',
            'prix.required' => 'Le prix est obligatoire.',
            'prix.numeric' => 'Le prix doit être un nombre.',
            'prix.min' => 'Le prix ne peut pas être négatif.',
        ]);

        // Vérifier l'unicité du libellé (sauf pour le type courant)
        $existant = TicketType::where('tenant_id', $user->tenant_id)
            ->where('libelle', $validated['libelle'])
            ->where('id', '!=', $id)
            ->first();

        if ($existant) {
            return back()->withErrors(['libelle' => 'Un autre type avec ce libellé existe déjà.']);
        }

        // Sauvegarde des anciennes valeurs pour l'audit
        $anciennes = [
            'libelle' => $ticketType->libelle,
            'prix' => (float) $ticketType->prix,
            'actif' => $ticketType->actif,
        ];

        DB::transaction(function () use ($ticketType, $user, $validated, $request, $anciennes) {
            $ticketType->update([
                'libelle' => $validated['libelle'],
                'prix' => $validated['prix'],
                'actif' => $validated['actif'],
            ]);

            AuditLog::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'action' => 'ticket_type.updated',
                'entite' => 'ticket_type',
                'entite_id' => $ticketType->id,
                'details' => [
                    'avant' => $anciennes,
                    'apres' => [
                        'libelle' => $validated['libelle'],
                        'prix' => (float) $validated['prix'],
                        'actif' => $validated['actif'],
                    ],
                ],
                'ip' => $request->ip(),
                'survenu_le' => Carbon::now(),
            ]);
        });

        return redirect()->route('ticket-types.index')
            ->with('success', 'Type de prestation modifié avec succès.');
    }

    /**
     * Active ou désactive un type (toggle rapide)
     */
    public function toggleActif(Request $request, $id)
    {
        $this->ensureAdmin();

        $user = auth()->user();

        $ticketType = TicketType::where('id', $id)
            ->where('tenant_id', $user->tenant_id)
            ->firstOrFail();

        $ancienEtat = $ticketType->actif;

        DB::transaction(function () use ($ticketType, $user, $ancienEtat, $request) {
            $ticketType->update(['actif' => !$ticketType->actif]);

            AuditLog::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'action' => $ticketType->actif ? 'ticket_type.activated' : 'ticket_type.deactivated',
                'entite' => 'ticket_type',
                'entite_id' => $ticketType->id,
                'details' => [
                    'libelle' => $ticketType->libelle,
                    'avant' => $ancienEtat,
                    'apres' => $ticketType->actif,
                ],
                'ip' => $request->ip(),
                'survenu_le' => Carbon::now(),
            ]);
        });

        $message = $ticketType->actif
            ? 'Type de prestation réactivé.'
            : 'Type de prestation désactivé.';

        return redirect()->back()->with('success', $message);
    }
}
