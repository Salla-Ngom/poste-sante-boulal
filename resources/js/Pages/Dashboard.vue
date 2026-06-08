<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

defineProps({
    sessionOuverte: Object,
    sessionPharmacieOuverte: Object,
    stats: Object,
    derniersTickets: Array,
    derniersTicketsPharmacie: Array,
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const dateAujourdhui = computed(() => {
    return new Date().toLocaleDateString("fr-FR", {
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric",
    });
});

const salutation = computed(() => {
    const heure = new Date().getHours();
    if (heure < 12) return "Bonjour";
    if (heure < 18) return "Bon après-midi";
    return "Bonsoir";
});

const formatFCFA = (montant) => {
    return new Intl.NumberFormat("fr-FR").format(montant || 0) + " FCFA";
};

const formatHeure = (dateString) => {
    return new Date(dateString).toLocaleTimeString("fr-FR", {
        hour: "2-digit",
        minute: "2-digit",
    });
};

const numeroFormate = (numero) => {
    return String(numero).padStart(4, "0");
};
</script>

<template>

    <Head title="Tableau de bord" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Tableau de bord
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- ===== Bandeau de bienvenue ===== -->
                <div class="rounded-2xl shadow-lg p-6 sm:p-8 text-white"
                    :class="user.role === 'pharmacien'
                        ? 'bg-gradient-to-r from-purple-600 to-pink-600'
                        : 'bg-gradient-to-r from-emerald-600 to-teal-600'">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <p class="text-sm mb-1 capitalize"
                                :class="user.role === 'pharmacien' ? 'text-purple-100' : 'text-emerald-100'">
                                {{ dateAujourdhui }}
                            </p>
                            <h1 class="text-2xl sm:text-3xl font-bold mb-1">
                                {{ salutation }}, {{ user.name }}
                            </h1>
                            <p :class="user.role === 'pharmacien' ? 'text-purple-50' : 'text-emerald-50'">
                                <span v-if="user.role === 'admin'">Vue administrateur · Accès complet</span>
                                <span v-else-if="user.role === 'superviseur'">Vue superviseur · Lecture seule</span>
                                <span v-else-if="user.role === 'pharmacien'">Vue pharmacien · Stock et vente médicaments</span>
                                <span v-else>Vous êtes prêt(e) pour votre journée de caisse</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ===== Bandeau caissier : caisse Accueil ===== -->
                <div v-if="user.role === 'caissier' && !sessionOuverte"
                    class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-3">
                    <div class="text-amber-600 text-2xl font-bold">!</div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-amber-900 mb-1">Aucune session de caisse ouverte</h3>
                        <p class="text-amber-800 text-sm mb-3">
                            Pour vendre des tickets, vous devez d'abord ouvrir votre caisse en saisissant le fond initial.
                        </p>
                        <Link :href="route('caisse.ouverture')"
                            class="inline-block px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold rounded-lg transition">
                            Ouvrir ma caisse
                        </Link>
                    </div>
                </div>

                <div v-if="user.role === 'caissier' && sessionOuverte"
                    class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex items-start gap-3">
                    <div class="text-emerald-600 text-2xl font-bold">OK</div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-emerald-900 mb-1">Session de caisse ouverte</h3>
                        <p class="text-emerald-800 text-sm mb-3">
                            Fond initial : {{ formatFCFA(sessionOuverte.fond_caisse_initial) }} · Vous pouvez vendre des tickets.
                        </p>
                        <Link :href="route('caisse.cloture')"
                            class="inline-block px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold rounded-lg transition">
                            Clôturer la caisse
                        </Link>
                    </div>
                </div>

                <!-- ===== Bandeau pharmacien : caisse Pharmacie ===== -->
                <div v-if="user.role === 'pharmacien' && !sessionPharmacieOuverte"
                    class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-3">
                    <div class="text-amber-600 text-2xl font-bold">!</div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-amber-900 mb-1">Aucune caisse pharmacie ouverte</h3>
                        <p class="text-amber-800 text-sm mb-3">
                            Pour vendre des médicaments, vous devez d'abord ouvrir votre caisse pharmacie en saisissant le fond initial.
                        </p>
                        <Link :href="route('pharmacy.caisse.ouverture')"
                            class="inline-block px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold rounded-lg transition">
                            Ouvrir ma caisse pharmacie
                        </Link>
                    </div>
                </div>

                <div v-if="user.role === 'pharmacien' && sessionPharmacieOuverte"
                    class="bg-purple-50 border border-purple-200 rounded-xl p-4 flex items-start gap-3">
                    <div class="text-purple-600 text-2xl font-bold">OK</div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-purple-900 mb-1">Caisse pharmacie ouverte</h3>
                        <p class="text-purple-800 text-sm mb-3">
                            Fond initial : {{ formatFCFA(sessionPharmacieOuverte.fond_caisse_initial) }} · Vous pouvez vendre des médicaments.
                        </p>
                        <div class="flex gap-2 flex-wrap">
                            <Link :href="route('pharmacy.vendre')"
                                class="inline-block px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold rounded-lg transition">
                                Nouvelle vente
                            </Link>
                            <Link :href="route('pharmacy.caisse.cloture')"
                                class="inline-block px-4 py-2 bg-white hover:bg-gray-50 border border-purple-300 text-purple-700 text-sm font-semibold rounded-lg transition">
                                Clôturer la caisse
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- ===== Bandeau info superviseur ===== -->
                <div v-if="user.role === 'superviseur'"
                    class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-start gap-3">
                    <div class="text-blue-600 text-2xl font-bold">i</div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-blue-900 mb-1">Mode lecture seule</h3>
                        <p class="text-blue-800 text-sm">
                            Vous pouvez consulter l'activité, les rapports et les statistiques. Les opérations de vente, caisse et dépenses sont réservées aux caissiers et administrateurs.
                        </p>
                    </div>
                </div>

                <!-- ===== Statistiques du jour : ADMIN/SUPERVISEUR (vue globale) ===== -->
                <div v-if="['admin', 'superviseur'].includes(user.role)">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Aujourd'hui — vue globale</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-sm font-medium text-gray-500">Tickets accueil</p>
                                <div class="w-9 h-9 bg-emerald-100 rounded-lg flex items-center justify-center text-emerald-600 font-bold">T</div>
                            </div>
                            <p class="text-3xl font-bold text-gray-900">{{ stats.ticketsAujourdhui }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ formatFCFA(stats.recettesAujourdhui) }}</p>
                        </div>

                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-sm font-medium text-gray-500">Ventes pharmacie</p>
                                <div class="w-9 h-9 bg-purple-100 rounded-lg flex items-center justify-center text-purple-600 font-bold">P</div>
                            </div>
                            <p class="text-3xl font-bold text-gray-900">{{ stats.ventesPharmacieAujourdhui }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ formatFCFA(stats.recettesPharmacieAujourdhui) }}</p>
                        </div>

                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-sm font-medium text-gray-500">Médicaments en alerte</p>
                                <div class="w-9 h-9 bg-amber-100 rounded-lg flex items-center justify-center text-amber-600 font-bold">A</div>
                            </div>
                            <p class="text-3xl font-bold text-amber-600">{{ stats.medicamentsEnAlerte }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ stats.medicamentsEnRupture }} en rupture</p>
                        </div>

                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-sm font-medium text-gray-500">Dépenses</p>
                                <div class="w-9 h-9 bg-red-100 rounded-lg flex items-center justify-center text-red-600 font-bold">D</div>
                            </div>
                            <p class="text-3xl font-bold text-red-600">{{ formatFCFA(stats.depensesAujourdhui) }}</p>
                            <p class="text-xs text-gray-500 mt-1">sorties aujourd'hui</p>
                        </div>
                    </div>
                </div>

                <!-- ===== Statistiques du jour : CAISSIER ===== -->
                <div v-if="user.role === 'caissier'">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Aujourd'hui</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-sm font-medium text-gray-500">Tickets vendus</p>
                                <div class="w-9 h-9 bg-emerald-100 rounded-lg flex items-center justify-center text-emerald-600 font-bold">T</div>
                            </div>
                            <p class="text-3xl font-bold text-gray-900">{{ stats.ticketsAujourdhui }}</p>
                            <p class="text-xs text-gray-500 mt-1">tickets émis</p>
                        </div>

                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-sm font-medium text-gray-500">Recettes</p>
                                <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 font-bold">F</div>
                            </div>
                            <p class="text-3xl font-bold text-gray-900">{{ formatFCFA(stats.recettesAujourdhui) }}</p>
                            <p class="text-xs text-gray-500 mt-1">encaissé aujourd'hui</p>
                        </div>

                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-sm font-medium text-gray-500">État caisse</p>
                                <div class="w-9 h-9 bg-amber-100 rounded-lg flex items-center justify-center text-amber-600 font-bold">C</div>
                            </div>
                            <p class="text-2xl font-bold" :class="sessionOuverte ? 'text-emerald-600' : 'text-gray-400'">
                                {{ sessionOuverte ? "Ouverte" : "Fermée" }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">session actuelle</p>
                        </div>

                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-sm font-medium text-gray-500">Dépenses</p>
                                <div class="w-9 h-9 bg-red-100 rounded-lg flex items-center justify-center text-red-600 font-bold">D</div>
                            </div>
                            <p class="text-3xl font-bold text-red-600">{{ formatFCFA(stats.depensesAujourdhui) }}</p>
                            <p class="text-xs text-gray-500 mt-1">sorties de la session</p>
                        </div>
                    </div>
                </div>

                <!-- ===== Statistiques du jour : PHARMACIEN ===== -->
                <div v-if="user.role === 'pharmacien'">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Aujourd'hui</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-sm font-medium text-gray-500">Ventes pharmacie</p>
                                <div class="w-9 h-9 bg-purple-100 rounded-lg flex items-center justify-center text-purple-600 font-bold">V</div>
                            </div>
                            <p class="text-3xl font-bold text-gray-900">{{ stats.ventesPharmacieAujourdhui }}</p>
                            <p class="text-xs text-gray-500 mt-1">tickets émis</p>
                        </div>

                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-sm font-medium text-gray-500">Recettes</p>
                                <div class="w-9 h-9 bg-pink-100 rounded-lg flex items-center justify-center text-pink-600 font-bold">F</div>
                            </div>
                            <p class="text-3xl font-bold text-gray-900">{{ formatFCFA(stats.recettesPharmacieAujourdhui) }}</p>
                            <p class="text-xs text-gray-500 mt-1">encaissé aujourd'hui</p>
                        </div>

                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-sm font-medium text-gray-500">État caisse</p>
                                <div class="w-9 h-9 bg-amber-100 rounded-lg flex items-center justify-center text-amber-600 font-bold">C</div>
                            </div>
                            <p class="text-2xl font-bold" :class="sessionPharmacieOuverte ? 'text-purple-600' : 'text-gray-400'">
                                {{ sessionPharmacieOuverte ? "Ouverte" : "Fermée" }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">caisse pharmacie</p>
                        </div>

                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-sm font-medium text-gray-500">Stock</p>
                                <div class="w-9 h-9 bg-red-100 rounded-lg flex items-center justify-center text-red-600 font-bold">!</div>
                            </div>
                            <p class="text-3xl font-bold text-amber-600">{{ stats.medicamentsEnAlerte }}</p>
                            <p class="text-xs text-gray-500 mt-1">
                                <span v-if="stats.medicamentsEnRupture > 0" class="text-red-600 font-semibold">
                                    {{ stats.medicamentsEnRupture }} en rupture
                                </span>
                                <span v-else>en alerte</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ===== Actions rapides ===== -->
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                        <span v-if="user.role === 'superviseur'">Consultations rapides</span>
                        <span v-else>Actions rapides</span>
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

                        <!-- Vendre un ticket : caissier + admin -->
                        <Link v-if="['caissier', 'admin'].includes(user.role)" :href="route('tickets.vendre')"
                            class="bg-white rounded-xl border border-gray-100 p-5 hover:border-emerald-300 hover:shadow-lg transition group">
                            <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center text-2xl mb-3 group-hover:bg-emerald-600 group-hover:text-white transition font-bold">+</div>
                            <h3 class="font-semibold text-gray-900 mb-1">Vendre un ticket</h3>
                            <p class="text-sm text-gray-500">Émettre un nouveau ticket pour un patient</p>
                        </Link>

                        <!-- Vendre des médicaments : pharmacien + admin -->
                        <Link v-if="['pharmacien', 'admin'].includes(user.role)" :href="route('pharmacy.vendre')"
                            class="bg-white rounded-xl border border-gray-100 p-5 hover:border-purple-300 hover:shadow-lg transition group">
                            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center text-2xl mb-3 group-hover:bg-purple-600 group-hover:text-white transition font-bold">V</div>
                            <h3 class="font-semibold text-gray-900 mb-1">Vendre des médicaments</h3>
                            <p class="text-sm text-gray-500">Nouvelle vente pharmacie</p>
                        </Link>

                        <!-- Historique tickets : tous sauf pharmacien -->
                        <Link v-if="user.role !== 'pharmacien'" :href="route('tickets.index')"
                            class="bg-white rounded-xl border border-gray-100 p-5 hover:border-blue-300 hover:shadow-lg transition group">
                            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center text-2xl mb-3 group-hover:bg-blue-600 group-hover:text-white transition font-bold">H</div>
                            <h3 class="font-semibold text-gray-900 mb-1">Historique tickets</h3>
                            <p class="text-sm text-gray-500">Consulter les tickets accueil</p>
                        </Link>

                        <!-- Historique pharmacie : tous sauf caissier -->
                        <Link v-if="user.role !== 'caissier'" :href="route('pharmacy.historique')"
                            class="bg-white rounded-xl border border-gray-100 p-5 hover:border-violet-300 hover:shadow-lg transition group">
                            <div class="w-12 h-12 bg-violet-100 rounded-xl flex items-center justify-center text-2xl mb-3 group-hover:bg-violet-600 group-hover:text-white transition font-bold">H</div>
                            <h3 class="font-semibold text-gray-900 mb-1">Historique pharmacie</h3>
                            <p class="text-sm text-gray-500">Consulter les ventes pharmacie</p>
                        </Link>

                        <!-- Dépenses : caissier + admin (si session ouverte) -->
                        <Link v-if="sessionOuverte && ['caissier', 'admin'].includes(user.role)" :href="route('depenses.index')"
                            class="bg-white rounded-xl border border-gray-100 p-5 hover:border-red-300 hover:shadow-lg transition group">
                            <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center text-2xl mb-3 group-hover:bg-red-600 group-hover:text-white transition font-bold">$</div>
                            <h3 class="font-semibold text-gray-900 mb-1">Dépenses</h3>
                            <p class="text-sm text-gray-500">Enregistrer une sortie de caisse</p>
                        </Link>

                        <!-- Clôturer la caisse accueil : caissier + admin -->
                        <Link v-if="['caissier', 'admin'].includes(user.role)" :href="route('caisse.cloture')"
                            class="bg-white rounded-xl border border-gray-100 p-5 hover:border-purple-300 hover:shadow-lg transition group">
                            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center text-2xl mb-3 group-hover:bg-purple-600 group-hover:text-white transition font-bold">R</div>
                            <h3 class="font-semibold text-gray-900 mb-1">Clôturer caisse accueil</h3>
                            <p class="text-sm text-gray-500">Générer le rapport du jour</p>
                        </Link>

                        <!-- Caisse pharmacie : pharmacien + admin -->
                        <Link v-if="['pharmacien', 'admin'].includes(user.role)" :href="route('pharmacy.caisse.ouverture')"
                            class="bg-white rounded-xl border border-gray-100 p-5 hover:border-pink-300 hover:shadow-lg transition group">
                            <div class="w-12 h-12 bg-pink-100 rounded-xl flex items-center justify-center text-2xl mb-3 group-hover:bg-pink-600 group-hover:text-white transition font-bold">P</div>
                            <h3 class="font-semibold text-gray-900 mb-1">Caisse pharmacie</h3>
                            <p class="text-sm text-gray-500">Ouvrir / clôturer la caisse pharmacie</p>
                        </Link>

                        <!-- Rapports : superviseur + admin -->
                        <Link v-if="['superviseur', 'admin'].includes(user.role)" :href="route('rapports.index')"
                            class="bg-white rounded-xl border border-gray-100 p-5 hover:border-amber-300 hover:shadow-lg transition group">
                            <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center text-2xl mb-3 group-hover:bg-amber-600 group-hover:text-white transition font-bold">S</div>
                            <h3 class="font-semibold text-gray-900 mb-1">Rapports d'activité</h3>
                            <p class="text-sm text-gray-500">Statistiques mensuelles et exports</p>
                        </Link>

                        <!-- Agents : admin uniquement -->
                        <Link v-if="user.role === 'admin'" :href="route('agents.index')"
                            class="bg-white rounded-xl border border-gray-100 p-5 hover:border-pink-300 hover:shadow-lg transition group">
                            <div class="w-12 h-12 bg-pink-100 rounded-xl flex items-center justify-center text-2xl mb-3 group-hover:bg-pink-600 group-hover:text-white transition font-bold">A</div>
                            <h3 class="font-semibold text-gray-900 mb-1">Gérer les agents</h3>
                            <p class="text-sm text-gray-500">Ajouter ou désactiver des utilisateurs</p>
                        </Link>

                        <!-- Types de tickets : admin uniquement -->
                        <Link v-if="user.role === 'admin'" :href="route('ticket-types.index')"
                            class="bg-white rounded-xl border border-gray-100 p-5 hover:border-teal-300 hover:shadow-lg transition group">
                            <div class="w-12 h-12 bg-teal-100 rounded-xl flex items-center justify-center text-2xl mb-3 group-hover:bg-teal-600 group-hover:text-white transition font-bold">P</div>
                            <h3 class="font-semibold text-gray-900 mb-1">Types de tickets</h3>
                            <p class="text-sm text-gray-500">Configurer les prestations et tarifs</p>
                        </Link>

                        <!-- Catalogue médicaments : admin uniquement -->
                        <Link v-if="user.role === 'admin'" :href="route('medicaments.index')"
                            class="bg-white rounded-xl border border-gray-100 p-5 hover:border-cyan-300 hover:shadow-lg transition group">
                            <div class="w-12 h-12 bg-cyan-100 rounded-xl flex items-center justify-center text-2xl mb-3 group-hover:bg-cyan-600 group-hover:text-white transition font-bold">M</div>
                            <h3 class="font-semibold text-gray-900 mb-1">Catalogue médicaments</h3>
                            <p class="text-sm text-gray-500">Ajouter et configurer les médicaments</p>
                        </Link>

                        <!-- Stock pharmacie : admin + pharmacien + superviseur -->
                        <Link v-if="['admin', 'pharmacien', 'superviseur'].includes(user.role)" :href="route('stocks.index')"
                            class="bg-white rounded-xl border border-gray-100 p-5 hover:border-purple-300 hover:shadow-lg transition group">
                            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center text-2xl mb-3 group-hover:bg-purple-600 group-hover:text-white transition font-bold">S</div>
                            <h3 class="font-semibold text-gray-900 mb-1">Stock pharmacie</h3>
                            <p class="text-sm text-gray-500">
                                <span v-if="user.role === 'admin'">Gestion complète du stock</span>
                                <span v-else-if="user.role === 'pharmacien'">Consultation et réception</span>
                                <span v-else>Consultation</span>
                            </p>
                        </Link>
                    </div>
                </div>

                <!-- ===== Activité récente CAISSIER : ses tickets ===== -->
                <div v-if="user.role === 'caissier' && derniersTickets.length > 0">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Activité récente</h2>
                        <Link :href="route('tickets.index')" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium">
                            Tout voir
                        </Link>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                        <ul class="divide-y divide-gray-100">
                            <li v-for="ticket in derniersTickets" :key="ticket.id" class="hover:bg-gray-50 transition">
                                <Link :href="route('tickets.recu', ticket.id)" class="flex items-center justify-between p-4 gap-4">
                                    <div class="flex items-center gap-3 min-w-0 flex-1">
                                        <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center text-emerald-600 font-bold text-xs flex-shrink-0">
                                            {{ numeroFormate(ticket.numero) }}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="font-medium text-gray-900 truncate">
                                                {{ ticket.ticket_type.libelle }}
                                            </p>
                                            <p class="text-xs text-gray-500">{{ formatHeure(ticket.emis_le) }}</p>
                                        </div>
                                    </div>
                                    <p class="font-semibold text-emerald-600">{{ formatFCFA(ticket.prix_paye) }}</p>
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- ===== Activité récente PHARMACIEN : ses ventes médicaments ===== -->
                <div v-if="user.role === 'pharmacien' && derniersTicketsPharmacie.length > 0">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Dernières ventes</h2>
                        <Link :href="route('pharmacy.historique')" class="text-sm text-purple-600 hover:text-purple-700 font-medium">
                            Tout voir
                        </Link>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                        <ul class="divide-y divide-gray-100">
                            <li v-for="ticket in derniersTicketsPharmacie" :key="ticket.id" class="hover:bg-gray-50 transition">
                                <Link :href="route('pharmacy.recu', ticket.id)" class="flex items-center justify-between p-4 gap-4">
                                    <div class="flex items-center gap-3 min-w-0 flex-1">
                                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center text-purple-600 font-bold text-xs flex-shrink-0">
                                            {{ numeroFormate(ticket.numero) }}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="font-medium text-gray-900 truncate">
                                                {{ ticket.patient_prenom }} {{ ticket.patient_nom }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                {{ ticket.lines.length }} ligne{{ ticket.lines.length > 1 ? 's' : '' }} · {{ formatHeure(ticket.emis_le) }}
                                            </p>
                                        </div>
                                    </div>
                                    <p class="font-semibold text-purple-600">{{ formatFCFA(ticket.total) }}</p>
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- ===== Activité récente ADMIN/SUPERVISEUR : 2 listes côte à côte ===== -->
                <div v-if="['admin', 'superviseur'].includes(user.role)" class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                    <!-- Tickets accueil récents -->
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900">Tickets accueil récents</h2>
                            <Link :href="route('tickets.index')" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium">
                                Tout voir
                            </Link>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                            <div v-if="derniersTickets.length === 0" class="p-8 text-center">
                                <p class="text-sm text-gray-400">Aucun ticket émis</p>
                            </div>
                            <ul v-else class="divide-y divide-gray-100">
                                <li v-for="ticket in derniersTickets" :key="ticket.id" class="hover:bg-gray-50 transition">
                                    <Link :href="route('tickets.recu', ticket.id)" class="flex items-center justify-between p-3 gap-3">
                                        <div class="flex items-center gap-2 min-w-0 flex-1">
                                            <div class="w-9 h-9 bg-emerald-100 rounded-lg flex items-center justify-center text-emerald-600 font-bold text-xs flex-shrink-0">
                                                {{ numeroFormate(ticket.numero) }}
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="font-medium text-sm text-gray-900 truncate">{{ ticket.ticket_type.libelle }}</p>
                                                <p class="text-xs text-gray-500">{{ ticket.user.name }} · {{ formatHeure(ticket.emis_le) }}</p>
                                            </div>
                                        </div>
                                        <p class="font-semibold text-emerald-600 text-sm">{{ formatFCFA(ticket.prix_paye) }}</p>
                                    </Link>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Ventes pharmacie récentes -->
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900">Ventes pharmacie récentes</h2>
                            <Link :href="route('pharmacy.historique')" class="text-sm text-purple-600 hover:text-purple-700 font-medium">
                                Tout voir
                            </Link>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                            <div v-if="derniersTicketsPharmacie.length === 0" class="p-8 text-center">
                                <p class="text-sm text-gray-400">Aucune vente pharmacie</p>
                            </div>
                            <ul v-else class="divide-y divide-gray-100">
                                <li v-for="ticket in derniersTicketsPharmacie" :key="ticket.id" class="hover:bg-gray-50 transition">
                                    <Link :href="route('pharmacy.recu', ticket.id)" class="flex items-center justify-between p-3 gap-3">
                                        <div class="flex items-center gap-2 min-w-0 flex-1">
                                            <div class="w-9 h-9 bg-purple-100 rounded-lg flex items-center justify-center text-purple-600 font-bold text-xs flex-shrink-0">
                                                {{ numeroFormate(ticket.numero) }}
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="font-medium text-sm text-gray-900 truncate">{{ ticket.patient_prenom }} {{ ticket.patient_nom }}</p>
                                                <p class="text-xs text-gray-500">{{ ticket.user.name }} · {{ formatHeure(ticket.emis_le) }}</p>
                                            </div>
                                        </div>
                                        <p class="font-semibold text-purple-600 text-sm">{{ formatFCFA(ticket.total) }}</p>
                                    </Link>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ===== Vue d'ensemble (semaine + mois) — superviseur/admin ===== -->
                <div v-if="['superviseur', 'admin'].includes(user.role)" class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <h3 class="font-semibold text-gray-900 mb-4">Cette semaine</h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Ventes totales</span>
                                <span class="font-semibold text-gray-900">{{ stats.ticketsSemaine }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Recettes totales</span>
                                <span class="font-semibold text-emerald-600">{{ formatFCFA(stats.recettesSemaine) }}</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-3">Accueil + Pharmacie combinés</p>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <h3 class="font-semibold text-gray-900 mb-4">Ce mois-ci</h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Ventes totales</span>
                                <span class="font-semibold text-gray-900">{{ stats.ticketsMois }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Recettes totales</span>
                                <span class="font-semibold text-emerald-600">{{ formatFCFA(stats.recettesMois) }}</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-3">Accueil + Pharmacie combinés</p>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
