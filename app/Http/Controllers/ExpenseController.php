<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CashSession;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use \App\Http\Controllers\Concerns\AuthorizesActions;

class ExpenseController extends Controller
{
    use AuthorizesActions;
    /**
     * Affiche la page des dépenses de la session en cours
     */
    public function index()
    {
        $this->ensureCanOperate();
        $user = auth()->user();

        // Récupère la session ouverte de l'utilisateur
        $session = CashSession::where('user_id', $user->id)
            ->where('statut', 'ouverte')
            ->first();

        if (!$session) {
            return redirect()->route('dashboard')
                ->with('error', 'Vous devez avoir une session de caisse ouverte pour gérer les dépenses.');
        }

        // Récupère les dépenses de cette session
        $depenses = Expense::with('user')
            ->where('cash_session_id', $session->id)
            ->orderBy('depense_le', 'desc')
            ->get();

        $totalDepenses = (float) $depenses->sum('montant');

        return Inertia::render('Depenses/Index', [
            'session' => $session,
            'depenses' => $depenses,
            'totalDepenses' => $totalDepenses,
        ]);
    }

    /**
     * Enregistre une nouvelle dépense
     */
    public function store(Request $request)
    {
        $this->ensureCanOperate();
        $user = auth()->user();

        $validated = $request->validate([
            'libelle' => 'required|string|max:200',
            'montant' => 'required|numeric|min:1',
        ], [
            'libelle.required' => 'Le libellé est obligatoire.',
            'libelle.max' => 'Le libellé ne doit pas dépasser 200 caractères.',
            'montant.required' => 'Le montant est obligatoire.',
            'montant.numeric' => 'Le montant doit être un nombre.',
            'montant.min' => 'Le montant doit être supérieur à 0.',
        ]);

        // Vérifie qu'une session est bien ouverte
        $session = CashSession::where('user_id', $user->id)
            ->where('statut', 'ouverte')
            ->first();

        if (!$session) {
            return redirect()->route('dashboard')
                ->with('error', 'Aucune session de caisse ouverte.');
        }

        DB::transaction(function () use ($user, $session, $validated, $request) {
            $depense = Expense::create([
                'tenant_id' => $user->tenant_id,
                'cash_session_id' => $session->id,
                'user_id' => $user->id,
                'libelle' => $validated['libelle'],
                'montant' => $validated['montant'],
                'depense_le' => Carbon::now(),
            ]);

            AuditLog::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'action' => 'expense.recorded',
                'entite' => 'expense',
                'entite_id' => $depense->id,
                'details' => [
                    'libelle' => $depense->libelle,
                    'montant' => (float) $depense->montant,
                    'cash_session_id' => $session->id,
                ],
                'ip' => $request->ip(),
                'survenu_le' => Carbon::now(),
            ]);
        });

        return redirect()->route('depenses.index')
            ->with('success', 'Dépense enregistrée avec succès.');
    }
}
