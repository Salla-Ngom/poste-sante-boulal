<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesActions;
use App\Models\AuditLog;
use App\Models\CashReport;
use App\Models\CashSession;
use App\Models\Expense;
use App\Models\PharmacyTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PharmacyCashSessionController extends Controller
{
    use AuthorizesActions;

    public function create()
    {
        $this->ensureCanOperatePharmacie();
        $user = auth()->user();

        $sessionExistante = CashSession::where('user_id', $user->id)
            ->where('statut', 'ouverte')
            ->where('type_caisse', 'pharmacie')
            ->first();

        if ($sessionExistante) {
            return redirect()->route('dashboard')
                ->with('error', 'Vous avez déjà une session Pharmacie ouverte.');
        }

        return Inertia::render('Pharmacy/Ouverture');
    }

    public function store(Request $request)
    {
        $this->ensureCanOperatePharmacie();
        $user = auth()->user();

        $validated = $request->validate([
            'fond_caisse_initial' => 'required|numeric|min:0',
        ]);

        $sessionExistante = CashSession::where('user_id', $user->id)
            ->where('statut', 'ouverte')
            ->where('type_caisse', 'pharmacie')
            ->first();

        if ($sessionExistante) {
            return redirect()->route('dashboard')->with('error', 'Une session Pharmacie est déjà ouverte.');
        }

        $session = CashSession::create([
            'tenant_id' => $user->tenant_id,
            'type_caisse' => 'pharmacie',
            'user_id' => $user->id,
            'ouverte_le' => Carbon::now(),
            'fond_caisse_initial' => $validated['fond_caisse_initial'],
            'statut' => 'ouverte',
        ]);

        AuditLog::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'action' => 'pharmacy_session.opened',
            'entite' => 'cash_session',
            'entite_id' => $session->id,
            'details' => [
                'type_caisse' => 'pharmacie',
                'fond_caisse_initial' => (float) $session->fond_caisse_initial,
            ],
            'ip' => $request->ip(),
            'survenu_le' => Carbon::now(),
        ]);

        return redirect()->route('dashboard')->with('success', 'Caisse Pharmacie ouverte avec succès.');
    }

    public function showCloture()
    {
        $this->ensureCanOperatePharmacie();
        $user = auth()->user();

        $session = CashSession::where('user_id', $user->id)
            ->where('statut', 'ouverte')
            ->where('type_caisse', 'pharmacie')
            ->first();

        if (!$session) {
            return redirect()->route('dashboard')->with('error', 'Aucune session Pharmacie ouverte à clôturer.');
        }

        $totalVentes = (float) PharmacyTicket::where('cash_session_id', $session->id)
            ->where('statut', 'actif')->sum('total');

        $totalDepenses = (float) Expense::where('cash_session_id', $session->id)->sum('montant');

        $nombreTickets = PharmacyTicket::where('cash_session_id', $session->id)
            ->where('statut', 'actif')->count();

        $montantAttendu = (float) $session->fond_caisse_initial + $totalVentes - $totalDepenses;

        return Inertia::render('Pharmacy/Cloture', [
            'session' => $session,
            'totalVentes' => $totalVentes,
            'totalDepenses' => $totalDepenses,
            'nombreTickets' => $nombreTickets,
            'montantAttendu' => $montantAttendu,
        ]);
    }

    public function cloturer(Request $request)
    {
        $this->ensureCanOperatePharmacie();
        $user = auth()->user();

        $session = CashSession::where('user_id', $user->id)
            ->where('statut', 'ouverte')
            ->where('type_caisse', 'pharmacie')
            ->first();

        if (!$session) {
            return redirect()->route('dashboard')->with('error', 'Aucune session Pharmacie ouverte à clôturer.');
        }

        $validated = $request->validate([
            'montant_compte' => 'required|numeric|min:0',
        ]);

        $totalVentes = (float) PharmacyTicket::where('cash_session_id', $session->id)
            ->where('statut', 'actif')->sum('total');

        $totalDepenses = (float) Expense::where('cash_session_id', $session->id)->sum('montant');

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
                'action' => 'pharmacy_session.closed',
                'entite' => 'cash_session',
                'entite_id' => $session->id,
                'details' => [
                    'type_caisse' => 'pharmacie',
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

        return redirect()->route('pharmacy.caisse.rapport', $session->id)
            ->with('success', 'Caisse Pharmacie clôturée. Rapport généré.');
    }

    private function genererRapportPDF($session, $user)
    {
        $session->load(['user', 'tenant']);

        $tickets = PharmacyTicket::with('lines')
            ->where('cash_session_id', $session->id)
            ->where('statut', 'actif')
            ->orderBy('numero')
            ->get();

        $nombreTickets = $tickets->count();
        $totalVentes = (float) $tickets->sum('total');
        $totalDepenses = (float) Expense::where('cash_session_id', $session->id)->sum('montant');
        $montantAttendu = (float) $session->fond_caisse_initial + $totalVentes - $totalDepenses;

        $duree = Carbon::parse($session->ouverte_le)->diff(Carbon::parse($session->fermee_le));
        $dureeStr = '';
        if ($duree->h > 0) $dureeStr .= $duree->h . 'h ';
        $dureeStr .= $duree->i . 'min';

        $donnees = [
            'session' => $session,
            'pharmacien' => $session->user,
            'tenant' => $session->tenant,
            'tickets' => $tickets,
            'nombreTickets' => $nombreTickets,
            'totalVentes' => $totalVentes,
            'totalDepenses' => $totalDepenses,
            'montantAttendu' => $montantAttendu,
            'duree' => $dureeStr,
            'hash' => '',
        ];

        $htmlSansHash = view('pdfs.rapport_pharmacie', $donnees)->render();
        $hash = hash('sha256', $htmlSansHash . $session->id . $session->fermee_le);
        $donnees['hash'] = $hash;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfs.rapport_pharmacie', $donnees);
        $pdf->setPaper('A4', 'portrait');

        $filename = 'pharmacie_' . Carbon::parse($session->fermee_le)->format('Y-m-d_His') . '_' . substr($session->id, 0, 8) . '.pdf';
        $path = 'rapports/' . $session->tenant_id . '/pharmacie/' . $filename;

        Storage::disk('local')->put($path, $pdf->output());

        CashReport::create([
            'cash_session_id' => $session->id,
            'contenu_hash' => $hash,
            'pdf_path' => $path,
            'genere_le' => Carbon::now(),
        ]);
    }

    /**
     * 🐛 FIX BUG DATE : Sérialisation explicite des dates pour éviter
     * que ouverte_le affiche la même valeur que fermee_le sur la vue.
     */
    public function afficherRapport($id)
    {
        $user = auth()->user();

        $session = CashSession::with(['user', 'cashReport'])
            ->where('id', $id)
            ->where('tenant_id', $user->tenant_id)
            ->where('type_caisse', 'pharmacie')
            ->firstOrFail();

        if ($user->role === 'pharmacien' && $session->user_id !== $user->id) {
            abort(403);
        }

        // 🔧 FIX : sérialisation EXPLICITE des dates en ISO 8601
        return Inertia::render('Pharmacy/Rapport', [
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
                'ouverte_le' => $session->ouverte_le?->toIso8601String(),
                'fermee_le' => $session->fermee_le?->toIso8601String(),
                'created_at' => $session->created_at?->toIso8601String(),
                'updated_at' => $session->updated_at?->toIso8601String(),
                'cash_report' => $session->cashReport,
            ],
        ]);
    }

    public function telechargerRapport($id)
    {
        $user = auth()->user();

        $session = CashSession::with('cashReport')
            ->where('id', $id)
            ->where('tenant_id', $user->tenant_id)
            ->where('type_caisse', 'pharmacie')
            ->firstOrFail();

        if ($user->role === 'pharmacien' && $session->user_id !== $user->id) {
            abort(403);
        }

        if (!$session->cashReport) {
            return back()->with('error', 'Aucun rapport disponible.');
        }

        if (!Storage::disk('local')->exists($session->cashReport->pdf_path)) {
            return back()->with('error', 'Fichier PDF introuvable.');
        }

        return Storage::disk('local')->download(
            $session->cashReport->pdf_path,
            'rapport_pharmacie_' . substr($session->id, 0, 8) . '.pdf'
        );
    }
}
