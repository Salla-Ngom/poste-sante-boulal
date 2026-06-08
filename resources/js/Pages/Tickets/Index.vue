<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    tickets: Object,
    ticketTypes: Array,
    caissiers: Array,
    stats: Object,
    filtres: Object,
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const form = useForm({
    debut: props.filtres.debut,
    fin: props.filtres.fin,
    ticket_type_id: props.filtres.ticket_type_id,
    user_id: props.filtres.user_id,
});

const filtresOuverts = ref(false);

const filtrer = () => {
    form.get(route('tickets.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const reinitialiser = () => {
    form.debut = new Date().toISOString().split('T')[0];
    form.fin = new Date().toISOString().split('T')[0];
    form.ticket_type_id = '';
    form.user_id = '';
    filtrer();
};

const formatFCFA = (montant) => {
    return new Intl.NumberFormat('fr-FR').format(montant || 0) + ' FCFA';
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

const formatHeure = (dateString) => {
    return new Date(dateString).toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const numeroFormate = (numero) => {
    return String(numero).padStart(4, '0');
};

// Filtres avancés actifs (pour afficher le compteur)
const nombreFiltresActifs = computed(() => {
    let count = 0;
    if (form.ticket_type_id) count++;
    if (form.user_id) count++;
    return count;
});
</script>

<template>
    <Head title="Historique des tickets" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Historique des tickets
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- ===== Statistiques rapides (2 cartes au lieu de 4) ===== -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <p class="text-xs text-gray-500 mb-1">Total période</p>
                        <p class="text-3xl font-bold text-gray-900">{{ stats.totalFiltres }}</p>
                        <p class="text-xs text-gray-400 mt-1">tickets émis</p>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <p class="text-xs text-gray-500 mb-1">Recettes</p>
                        <p class="text-3xl font-bold text-emerald-600">{{ formatFCFA(stats.recettesFiltrees) }}</p>
                        <p class="text-xs text-gray-400 mt-1">période filtrée</p>
                    </div>
                </div>

                <!-- ===== Filtres ===== -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm">

                    <!-- Filtres principaux toujours visibles -->
                    <div class="p-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Du</label>
                                <input
                                    v-model="form.debut"
                                    @change="filtrer"
                                    type="date"
                                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition text-sm"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Au</label>
                                <input
                                    v-model="form.fin"
                                    @change="filtrer"
                                    type="date"
                                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition text-sm"
                                />
                            </div>
                        </div>

                        <!-- Boutons toggle filtres avancés + reset -->
                        <div class="flex items-center justify-between mt-4">
                            <button
                                @click="filtresOuverts = !filtresOuverts"
                                class="text-sm font-medium text-emerald-600 hover:text-emerald-700 flex items-center gap-2"
                            >
                                <span>{{ filtresOuverts ? 'Masquer' : 'Afficher' }} les filtres avancés</span>
                                <span v-if="nombreFiltresActifs > 0" class="bg-emerald-100 text-emerald-700 text-xs px-2 py-0.5 rounded-full font-bold">
                                    {{ nombreFiltresActifs }}
                                </span>
                            </button>
                            <button
                                v-if="nombreFiltresActifs > 0"
                                @click="reinitialiser"
                                class="text-sm text-gray-500 hover:text-gray-700"
                            >
                                Réinitialiser
                            </button>
                        </div>
                    </div>

                    <!-- Filtres avancés (dépliables) -->
                    <div v-if="filtresOuverts" class="border-t border-gray-100 p-5 bg-gray-50">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Type de prestation</label>
                                <select
                                    v-model="form.ticket_type_id"
                                    @change="filtrer"
                                    class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition text-sm"
                                >
                                    <option value="">Toutes</option>
                                    <option v-for="type in ticketTypes" :key="type.id" :value="type.id">
                                        {{ type.libelle }}
                                    </option>
                                </select>
                            </div>
                            <div v-if="user.role !== 'caissier'">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Caissier</label>
                                <select
                                    v-model="form.user_id"
                                    @change="filtrer"
                                    class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition text-sm"
                                >
                                    <option value="">Tous</option>
                                    <option v-for="caissier in caissiers" :key="caissier.id" :value="caissier.id">
                                        {{ caissier.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===== Tableau des tickets ===== -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">N°</th>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Date / Heure</th>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Prestation</th>
                                    <th v-if="user.role !== 'caissier'" class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Caissier</th>
                                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Montant</th>
                                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-3">
                                        <span class="font-mono font-bold text-gray-900">
                                            {{ numeroFormate(ticket.numero) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="text-gray-900 font-medium">{{ formatDate(ticket.date_emission) }}</div>
                                        <div class="text-gray-500 text-xs">{{ formatHeure(ticket.emis_le) }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ ticket.ticket_type.libelle }}
                                    </td>
                                    <td v-if="user.role !== 'caissier'" class="px-4 py-3 text-sm text-gray-700">
                                        {{ ticket.user.name }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="font-semibold text-emerald-600">{{ formatFCFA(ticket.prix_paye) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <Link
                                            :href="route('tickets.recu', ticket.id)"
                                            class="text-emerald-600 hover:text-emerald-700 text-sm font-medium"
                                        >
                                            Voir reçu
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="tickets.data.length === 0">
                                    <td :colspan="user.role !== 'caissier' ? 6 : 5" class="px-4 py-12 text-center">
                                        <p class="text-gray-500 font-medium mb-1">Aucun ticket trouvé</p>
                                        <p class="text-sm text-gray-400">Essayez de modifier vos filtres</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="tickets.data.length > 0 && tickets.last_page > 1" class="px-4 py-4 border-t border-gray-100 flex items-center justify-between flex-wrap gap-3">
                        <p class="text-sm text-gray-500">
                            Page {{ tickets.current_page }} sur {{ tickets.last_page }} · {{ tickets.total }} tickets au total
                        </p>
                        <div class="flex items-center gap-2">
                            <Link
                                v-for="(link, i) in tickets.links"
                                :key="i"
                                :href="link.url || '#'"
                                v-html="link.label"
                                preserve-scroll
                                class="px-3 py-1.5 text-sm rounded-lg transition"
                                :class="[
                                    link.active ? 'bg-emerald-600 text-white' : 'bg-gray-50 text-gray-700 hover:bg-gray-100',
                                    !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''
                                ]"
                            />
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
