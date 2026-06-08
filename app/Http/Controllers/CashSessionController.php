<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesActions;
use App\Models\AuditLog;
use App\Models\CashSession;
use App\Models\Expense;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CashSessionController extends Controller
{
    use AuthorizesActions;

    /**
     * Affiche le formulaire d'ouverture de caisse Accueil
     */
    public function create()
    {
        $this->ensureCanOperateAccueil();
        $user = auth()->user();

        $sessionExistante = CashSession::where('user_id', $user->id)
            ->where('statut', 'ouverte')
            ->where('type_caisse', 'accueil')
            ->first();

        if ($sessionExistante) {
            return redirect()->route('dashboard')
                ->with('error', 'Vous avez déjà une session de caisse Accueil ouverte.');
        }

        return Inertia::render('Caisse/Ouverture');
    }

    /**
     * Ouvre une nouvelle session de caisse Accueil
     */
    public function store(Request $request)
    {
        $this->ensureCanOperateAccueil();
        $user = auth()->user();

        $validated = $request->validate([
            'fond_caisse_initial' => 'required|numeric|min:0',
        ], [
            'fond_caisse_initial.required' => 'Le fond de caisse est obligatoire.',
            'fond_caisse_initial.numeric' => 'Le fond de caisse doit être un nombre.',
            'fond_caisse_initial.min' => 'Le fond de caisse ne peut pas être négatif.',
        ]);

        $sessionExistante = CashSession::where('user_id', $user->id)
            ->where('statut', 'ouverte')
            ->where('type_caisse', 'accueil')
            ->first();

        if ($sessionExistante) {
            return redirect()->route('dashboard')
                ->with('error', 'Une session Accueil est déjà ouverte.');
        }

        $session = CashSession::create([
            'tenant_id' => $user->tenant_id,
            'type_caisse' => 'accueil',
            'user_id' => $user->id,
            'ouverte_le' => Carbon::now(),
            'fond_caisse_initial' => $validated['fond_caisse_initial'],
            'statut' => 'ouverte',
        ]);

        AuditLog::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'action' => 'cash_session.opened',
            'entite' => 'cash_session',
            'entite_id' => $session->id,
            'details' => [
                'type_caisse' => 'accueil',
                'fond_caisse_initial' => (float) $session->fond_caisse_initial,
            ],
            'ip' => $request->ip(),
            'survenu_le' => Carbon::now(),
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Caisse Accueil ouverte avec succès.');
    }

    /**
     * Affiche le formulaire de clôture de caisse Accueil
     */
    public function showCloture()
    {
        $this->ensureCanOperateAccueil();
        $user = auth()->user();

        $session = CashSession::where('user_id', $user->id)
            ->where('statut', 'ouverte')
            ->where('type_caisse', 'accueil')
            ->first();

        if (!$session) {
            return redirect()->route('dashboard')
                ->with('error', 'Aucune session Accueil ouverte à clôturer.');
        }

        $totalVentes = (float) Ticket::where('cash_session_id', $session->id)
            ->where('statut', 'actif')
            ->sum('prix_paye');

        $totalDepenses = (float) Expense::where('cash_session_id', $session->id)
            ->sum('montant');

        $nombreTickets = Ticket::where('cash_session_id', $session->id)
            ->where('statut', 'actif')
            ->count();

        $montantAttendu = (float) $session->fond_caisse_initial + $totalVentes - $totalDepenses;

        return Inertia::render('Caisse/Cloture', [
            'session' => $session,
            'totalVentes' => $totalVentes,
            'totalDepenses' => $totalDepenses,
            'nombreTickets' => $nombreTickets,
            'montantAttendu' => $montantAttendu,
        ]);
    }

    /**
     * Clôture la session de caisse Accueil en cours
     */
    public function cloturer(Request $request)
    {
        $this->ensureCanOperateAccueil();
        $user = auth()->user();

        $session = CashSession::where('user_id', $user->id)
            ->where('statut', 'ouverte')
            ->where('type_caisse', 'accueil')
            ->first();

        if (!$session) {
            return redirect()->route('dashboard')
                ->with('error', 'Aucune session Accueil ouverte à clôturer.');
        }

        $validated = $request->validate([
            'montant_compte' => 'required|numeric|min:0',
        ], [
            'montant_compte.required' => 'Le montant compté est obligatoire.',
            'montant_compte.numeric' => 'Le montant doit être un nombre.',
            'montant_compte.min' => 'Le montant ne peut pas être négatif.',
        ]);

        $totalVentes = (float) Ticket::where('cash_session_id', $session->id)
            ->where('statut', 'actif')
            ->sum('prix_paye');

        $totalDepenses = (float) Expense::where('cash_session_id', $session->id)
            ->sum('montant');

        $montantAttendu = (float) $session->fond_caisse_initial + $totalVentes - $totalDepenses;
        $ecart = (float) $validated['montant_compte'] - $montantAttendu;

        DB::transaction(function () use ($session, $user, $validated, $request, $ecart, $totalVentes, $totalDepenses, $montantAttendu) {

            $session->update([
                'fermee_le' => Carbon::now(),
                'montant_compte' => $validated['montant_compte'],
                'ecart' => $ecart,
                'statut' => 'fermee',
            ]);

            $this->genererRapportPDF($session, $user);

            AuditLog::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'action' => 'cash_session.closed',
                'entite' => 'cash_session',
                'entite_id' => $session->id,
                'details' => [
                    'type_caisse' => 'accueil',
                    'fond_initial' => (float) $session->fond_caisse_initial,
                    'total_ventes' => $totalVentes,
                    'total_depenses' => $totalDepenses,
                    'montant_attendu' => $montantAttendu,
                    'montant_compte' => (float) $validated['montant_compte'],
                    'ecart' => $ecart,
                ],
                'ip' => $request->ip(),
                'survenu_le' => Carbon::now(),
            ]);
        });

        return redirect()->route('caisse.rapport', $session->id)
            ->with('success', 'Caisse clôturée. Rapport généré avec succès.');
    }

    /**
     * Génère le rapport PDF de clôture et l'archive
     */
    private function genererRapportPDF($session, $user)
    {
        $session->load(['user', 'tenant']);

        $tickets = Ticket::with('ticketType')
            ->where('cash_session_id', $session->id)
            ->where('statut', 'actif')
            ->orderBy('numero')
            ->get();

        $nombreTickets = $tickets->count();
        $totalVentes = (float) $tickets->sum('prix_paye');
        $totalDepenses = (float) Expense::where('cash_session_id', $session->id)->sum('montant');
        $montantAttendu = (float) $session->fond_caisse_initial + $totalVentes - $totalDepenses;

        $duree = Carbon::parse($session->ouverte_le)->diff(Carbon::parse($session->fermee_le));
        $dureeStr = '';
        if ($duree->h > 0) $dureeStr .= $duree->h . 'h ';
        $dureeStr .= $duree->i . 'min';

        $donnees = [
            'session' => $session,
            'caissier' => $session->user,
            'tenant' => $session->tenant,
            'tickets' => $tickets,
            'nombreTickets' => $nombreTickets,
            'totalVentes' => $totalVentes,
            'totalDepenses' => $totalDepenses,
            'montantAttendu' => $montantAttendu,
            'duree' => $dureeStr,
            'hash' => '',
        ];

        $htmlSansHash = view('pdfs.rapport_caisse', $donnees)->render();
        $hash = hash('sha256', $htmlSansHash . $session->id . $session->fermee_le);
        $donnees['hash'] = $hash;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfs.rapport_caisse', $donnees);
        $pdf->setPaper('A4', 'portrait');

        $filename = 'rapport_' . Carbon::parse($session->fermee_le)->format('Y-m-d_His') . '_' . substr($session->id, 0, 8) . '.pdf';
        $path = 'rapports/' . $session->tenant_id . '/' . $filename;

        Storage::disk('local')->put($path, $pdf->output());

        \App\Models\CashReport::create([
            'cash_session_id' => $session->id,
            'contenu_hash' => $hash,
            'pdf_path' => $path,
            'genere_le' => Carbon::now(),
        ]);
    }

    /**
     * 🐛 FIX BUG DATE : Affiche le rapport avec sérialisation explicite des dates
     */
    public function afficherRapport($id)
    {
        $user = auth()->user();

        // Recharger fraîchement depuis la DB pour avoir les valeurs à jour
        $session = CashSession::with(['user', 'cashReport'])
            ->where('id', $id)
            ->where('tenant_id', $user->tenant_id)
            ->firstOrFail();

        // Sécurité : caissier/pharmacien ne voit que ses propres rapports
        if (in_array($user->role, ['caissier', 'pharmacien']) && $session->user_id !== $user->id) {
            abort(403);
        }

        // 🔧 FIX BUG : Sérialisation EXPLICITE des dates en ISO 8601
        // Évite les confusions entre ouverte_le et fermee_le
        return Inertia::render('Caisse/Rapport', [
            'session' => [
                'id' => $session->id,
                'tenant_id' => $session->tenant_id,
                'type_caisse' => $session->type_caisse,
                'user_id' => $session->user_id,
                'user' => $session->user,
                'statut' => $session->statut,
                'fond_caisse_initial' => (float) $session->fond_caisse_initial,
                'montant_compte' => $session->montant_compte !== null ? (float) $session->montant_compte : null,
                'ecart' => $session->ecart !== null ? (float) $session->ecart : null,
                // Dates explicitement formatées en ISO 8601 (JavaScript-friendly)
                'ouverte_le' => $session->ouverte_le?->toIso8601String(),
                'fermee_le' => $session->fermee_le?->toIso8601String(),
                'created_at' => $session->created_at?->toIso8601String(),
                'updated_at' => $session->updated_at?->toIso8601String(),
                'cash_report' => $session->cashReport,
            ],
        ]);
    }

    /**
     * Télécharge le PDF du rapport
     */
    public function telechargerRapport($id)
    {
        $user = auth()->user();

        $session = CashSession::with('cashReport')
            ->where('id', $id)
            ->where('tenant_id', $user->tenant_id)
            ->firstOrFail();

        if (in_array($user->role, ['caissier', 'pharmacien']) && $session->user_id !== $user->id) {
            abort(403);
        }

        if (!$session->cashReport) {
            return back()->with('error', 'Aucun rapport disponible pour cette session.');
        }

        if (!Storage::disk('local')->exists($session->cashReport->pdf_path)) {
            return back()->with('error', 'Fichier PDF introuvable.');
        }

        return Storage::disk('local')->download(
            $session->cashReport->pdf_path,
            'rapport_caisse_' . substr($session->id, 0, 8) . '.pdf'
        );
    }
}
