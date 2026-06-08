<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AgentController extends Controller
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
     * Liste des agents avec statistiques
     */
    public function index()
    {
        $this->ensureAdmin();

        $user = auth()->user();
        $tenantId = $user->tenant_id;

        $agents = User::where('tenant_id', $tenantId)
            ->withCount(['tickets as total_ventes' => function ($q) {
                $q->where('statut', 'actif');
            }])
            ->withSum(['tickets as total_recettes' => function ($q) {
                $q->where('statut', 'actif');
            }], 'prix_paye')
            ->orderByDesc('actif')
            ->orderBy('name')
            ->get();

        return Inertia::render('Agents/Index', [
            'agents' => $agents,
        ]);
    }

    /**
     * Affiche le formulaire de création
     */
    public function create()
    {
        $this->ensureAdmin();

        return Inertia::render('Agents/Form', [
            'agent' => null,
        ]);
    }

    /**
     * Enregistre un nouvel agent
     */
    public function store(Request $request)
    {
        $this->ensureAdmin();

        $admin = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::in(['caissier', 'superviseur', 'admin', 'pharmacien'])],
            'actif' => 'required|boolean',
        ], [
            'name.required' => 'Le nom est obligatoire.',
            'email.required' => 'L\'email est obligatoire.',
            'email.email' => 'L\'email doit être valide.',
            'email.unique' => 'Un compte avec cet email existe déjà.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'role.required' => 'Le rôle est obligatoire.',
            'role.in' => 'Le rôle est invalide.',
        ]);

        DB::transaction(function () use ($admin, $validated, $request) {
            $agent = User::create([
                'tenant_id' => $admin->tenant_id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'], // hashé automatiquement par le cast
                'role' => $validated['role'],
                'actif' => $validated['actif'],
                'email_verified_at' => Carbon::now(),
            ]);

            AuditLog::create([
                'tenant_id' => $admin->tenant_id,
                'user_id' => $admin->id,
                'action' => 'user.created',
                'entite' => 'user',
                'entite_id' => $agent->id,
                'details' => [
                    'name' => $agent->name,
                    'email' => $agent->email,
                    'role' => $agent->role,
                    'actif' => $agent->actif,
                ],
                'ip' => $request->ip(),
                'survenu_le' => Carbon::now(),
            ]);
        });

        return redirect()->route('agents.index')
            ->with('success', 'Agent créé avec succès. Communiquez-lui ses identifiants.');
    }

    /**
     * Affiche le formulaire d'édition
     */
    public function edit($id)
    {
        $this->ensureAdmin();

        $admin = auth()->user();

        $agent = User::where('id', $id)
            ->where('tenant_id', $admin->tenant_id)
            ->firstOrFail();

        return Inertia::render('Agents/Form', [
            'agent' => $agent,
        ]);
    }

    /**
     * Met à jour un agent existant
     */
    public function update(Request $request, $id)
    {
        $this->ensureAdmin();

        $admin = auth()->user();

        $agent = User::where('id', $id)
            ->where('tenant_id', $admin->tenant_id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($agent->id)],
            'role' => ['required', Rule::in(['caissier', 'superviseur', 'admin', 'pharmacien'])],
            'actif' => 'required|boolean',
        ], [
            'name.required' => 'Le nom est obligatoire.',
            'email.required' => 'L\'email est obligatoire.',
            'email.email' => 'L\'email doit être valide.',
            'email.unique' => 'Un autre compte utilise déjà cet email.',
            'role.required' => 'Le rôle est obligatoire.',
            'role.in' => 'Le rôle est invalide.',
        ]);

        // Sécurité : un admin ne peut pas modifier son propre rôle ni se désactiver
        if ($agent->id === $admin->id) {
            if ($validated['role'] !== $admin->role) {
                return back()->withErrors(['role' => 'Vous ne pouvez pas modifier votre propre rôle.']);
            }
            if (!$validated['actif']) {
                return back()->withErrors(['actif' => 'Vous ne pouvez pas désactiver votre propre compte.']);
            }
        }

        // Sauvegarde des anciennes valeurs pour audit
        $anciennes = [
            'name' => $agent->name,
            'email' => $agent->email,
            'role' => $agent->role,
            'actif' => $agent->actif,
        ];

        DB::transaction(function () use ($agent, $admin, $validated, $request, $anciennes) {
            $agent->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
                'actif' => $validated['actif'],
            ]);

            AuditLog::create([
                'tenant_id' => $admin->tenant_id,
                'user_id' => $admin->id,
                'action' => 'user.updated',
                'entite' => 'user',
                'entite_id' => $agent->id,
                'details' => [
                    'avant' => $anciennes,
                    'apres' => [
                        'name' => $validated['name'],
                        'email' => $validated['email'],
                        'role' => $validated['role'],
                        'actif' => $validated['actif'],
                    ],
                ],
                'ip' => $request->ip(),
                'survenu_le' => Carbon::now(),
            ]);
        });

        return redirect()->route('agents.index')
            ->with('success', 'Agent modifié avec succès.');
    }

    /**
     * Réinitialise le mot de passe d'un agent
     */
    public function resetPassword(Request $request, $id)
    {
        $this->ensureAdmin();

        $admin = auth()->user();

        $agent = User::where('id', $id)
            ->where('tenant_id', $admin->tenant_id)
            ->firstOrFail();

        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation ne correspond pas.',
        ]);

        DB::transaction(function () use ($agent, $admin, $validated, $request) {
            $agent->update(['password' => $validated['password']]);

            AuditLog::create([
                'tenant_id' => $admin->tenant_id,
                'user_id' => $admin->id,
                'action' => 'user.password_reset',
                'entite' => 'user',
                'entite_id' => $agent->id,
                'details' => [
                    'agent_name' => $agent->name,
                    'agent_email' => $agent->email,
                ],
                'ip' => $request->ip(),
                'survenu_le' => Carbon::now(),
            ]);
        });

        return redirect()->route('agents.index')
            ->with('success', 'Mot de passe réinitialisé. Communiquez le nouveau mot de passe à l\'agent.');
    }

    /**
     * Active ou désactive un agent
     */
    public function toggleActif(Request $request, $id)
    {
        $this->ensureAdmin();

        $admin = auth()->user();

        $agent = User::where('id', $id)
            ->where('tenant_id', $admin->tenant_id)
            ->firstOrFail();

        // Sécurité : un admin ne peut pas se désactiver lui-même
        if ($agent->id === $admin->id) {
            return back()->with('error', 'Vous ne pouvez pas désactiver votre propre compte.');
        }

        $ancienEtat = $agent->actif;

        DB::transaction(function () use ($agent, $admin, $ancienEtat, $request) {
            $agent->update(['actif' => !$agent->actif]);

            AuditLog::create([
                'tenant_id' => $admin->tenant_id,
                'user_id' => $admin->id,
                'action' => $agent->actif ? 'user.activated' : 'user.deactivated',
                'entite' => 'user',
                'entite_id' => $agent->id,
                'details' => [
                    'agent_name' => $agent->name,
                    'agent_email' => $agent->email,
                    'avant' => $ancienEtat,
                    'apres' => $agent->actif,
                ],
                'ip' => $request->ip(),
                'survenu_le' => Carbon::now(),
            ]);
        });

        $message = $agent->actif
            ? 'Agent réactivé. Il peut à nouveau se connecter.'
            : 'Agent désactivé. Il ne pourra plus se connecter.';

        return redirect()->back()->with('success', $message);
    }
}
