<?php

use App\Http\Controllers\CashSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RapportController;
use App\Http\Controllers\TicketController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    // === Pharmacie : caisse pharmacie ===
    Route::get('/pharmacy/caisse/rapport/{id}', [\App\Http\Controllers\PharmacyCashSessionController::class, 'afficherRapport'])->name('pharmacy.caisse.rapport');
Route::get('/pharmacy/caisse/rapport/{id}/telecharger', [\App\Http\Controllers\PharmacyCashSessionController::class, 'telechargerRapport'])->name('pharmacy.caisse.rapport.telecharger');
Route::get('/pharmacy/caisse/ouverture', [\App\Http\Controllers\PharmacyCashSessionController::class, 'create'])->name('pharmacy.caisse.ouverture');
Route::post('/pharmacy/caisse/ouverture', [\App\Http\Controllers\PharmacyCashSessionController::class, 'store'])->name('pharmacy.caisse.store');
Route::get('/pharmacy/caisse/cloture', [\App\Http\Controllers\PharmacyCashSessionController::class, 'showCloture'])->name('pharmacy.caisse.cloture');
Route::post('/pharmacy/caisse/cloturer', [\App\Http\Controllers\PharmacyCashSessionController::class, 'cloturer'])->name('pharmacy.caisse.cloturer');

// === Pharmacie : vente médicaments ===
Route::get('/pharmacy/vendre', [\App\Http\Controllers\PharmacyTicketController::class, 'create'])->name('pharmacy.vendre');
Route::post('/pharmacy/vendre', [\App\Http\Controllers\PharmacyTicketController::class, 'store'])->name('pharmacy.store');
Route::get('/pharmacy/recu/{id}', [\App\Http\Controllers\PharmacyTicketController::class, 'recu'])->name('pharmacy.recu');
Route::get('/pharmacy/historique', [\App\Http\Controllers\PharmacyTicketController::class, 'index'])->name('pharmacy.historique');

    // Stocks (admin + pharmacien + superviseur en lecture)
Route::get('/stocks', [\App\Http\Controllers\StockController::class, 'index'])->name('stocks.index');
Route::post('/stocks/receptionner', [\App\Http\Controllers\StockController::class, 'receptionner'])->name('stocks.receptionner');
Route::post('/stocks/regulariser', [\App\Http\Controllers\StockController::class, 'regulariser'])->name('stocks.regulariser');
Route::get('/stocks/mouvements', [\App\Http\Controllers\StockController::class, 'mouvements'])->name('stocks.mouvements');

    // Gestion des médicaments (admin uniquement)
Route::get('/medicaments', [\App\Http\Controllers\MedicamentController::class, 'index'])->name('medicaments.index');
Route::get('/medicaments/stats', [\App\Http\Controllers\MedicamentStatsController::class, 'index'])->name('medicaments.stats');
Route::get('/medicaments/nouveau', [\App\Http\Controllers\MedicamentController::class, 'create'])->name('medicaments.create');
Route::post('/medicaments', [\App\Http\Controllers\MedicamentController::class, 'store'])->name('medicaments.store');
Route::get('/medicaments/{id}/modifier', [\App\Http\Controllers\MedicamentController::class, 'edit'])->name('medicaments.edit');
Route::put('/medicaments/{id}', [\App\Http\Controllers\MedicamentController::class, 'update'])->name('medicaments.update');
Route::post('/medicaments/{id}/toggle', [\App\Http\Controllers\MedicamentController::class, 'toggleActif'])->name('medicaments.toggle');
    // Sessions de caisse
    Route::get('/caisse/ouverture', [CashSessionController::class, 'create'])->name('caisse.ouverture');
    Route::post('/caisse/ouverture', [CashSessionController::class, 'store'])->name('caisse.store');
    Route::get('/caisse/cloture', [CashSessionController::class, 'showCloture'])->name('caisse.cloture');
    Route::post('/caisse/cloture', [CashSessionController::class, 'cloturer'])->name('caisse.cloturer');
    Route::get('/caisse/rapport/{id}', [CashSessionController::class, 'afficherRapport'])->name('caisse.rapport');
    Route::get('/caisse/rapport/{id}/telecharger', [CashSessionController::class, 'telechargerRapport'])->name('caisse.rapport.telecharger');

// Gestion des agents (admin uniquement)
Route::get('/agents', [\App\Http\Controllers\AgentController::class, 'index'])->name('agents.index');
Route::get('/agents/nouveau', [\App\Http\Controllers\AgentController::class, 'create'])->name('agents.create');
Route::post('/agents', [\App\Http\Controllers\AgentController::class, 'store'])->name('agents.store');
Route::get('/agents/{id}/modifier', [\App\Http\Controllers\AgentController::class, 'edit'])->name('agents.edit');
Route::put('/agents/{id}', [\App\Http\Controllers\AgentController::class, 'update'])->name('agents.update');
Route::post('/agents/{id}/reset-password', [\App\Http\Controllers\AgentController::class, 'resetPassword'])->name('agents.reset-password');
Route::post('/agents/{id}/toggle', [\App\Http\Controllers\AgentController::class, 'toggleActif'])->name('agents.toggle');


// Dépenses
Route::get('/depenses', [\App\Http\Controllers\ExpenseController::class, 'index'])->name('depenses.index');
Route::post('/depenses', [\App\Http\Controllers\ExpenseController::class, 'store'])->name('depenses.store');
    // Tickets
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/vendre', [TicketController::class, 'create'])->name('tickets.vendre');
    Route::post('/tickets/vendre', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{id}/recu', [TicketController::class, 'recu'])->name('tickets.recu');

    // Rapports
    Route::get('/rapports', [RapportController::class, 'index'])->name('rapports.index');

    // Gestion des types de tickets (admin uniquement)
Route::get('/types-tickets', [\App\Http\Controllers\TicketTypeController::class, 'index'])->name('ticket-types.index');
Route::get('/types-tickets/nouveau', [\App\Http\Controllers\TicketTypeController::class, 'create'])->name('ticket-types.create');
Route::post('/types-tickets', [\App\Http\Controllers\TicketTypeController::class, 'store'])->name('ticket-types.store');
Route::get('/types-tickets/{id}/modifier', [\App\Http\Controllers\TicketTypeController::class, 'edit'])->name('ticket-types.edit');
Route::put('/types-tickets/{id}', [\App\Http\Controllers\TicketTypeController::class, 'update'])->name('ticket-types.update');
Route::post('/types-tickets/{id}/toggle', [\App\Http\Controllers\TicketTypeController::class, 'toggleActif'])->name('ticket-types.toggle');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
