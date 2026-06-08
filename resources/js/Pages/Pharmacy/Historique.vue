<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    tickets: Object,
    stats: Object,
    filtres: Object,
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const form = useForm({
    debut: props.filtres.debut,
    fin: props.filtres.fin,
    recherche: props.filtres.recherche,
});

const filtrer = () => {
    form.get(route('pharmacy.historique'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatFCFA = (montant) => {
    return new Intl.NumberFormat('fr-FR').format(montant || 0) + ' FCFA';
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
    });
};

const formatHeure = (dateString) => {
    return new Date(dateString).toLocaleTimeString('fr-FR', {
        hour: '2-digit', minute: '2-digit',
    });
};

const numeroFormate = (numero) => {
    return String(numero).padStart(4, '0');
};
</script>

<template>
    <Head title="Historique des ventes pharmacie" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Historique des ventes pharmacie
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- ===== Stats ===== -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <p class="text-xs text-gray-500 mb-1">Tickets pharmacie</p>
                        <p class="text-3xl font-bold text-gray-900">{{ stats.totalTickets }}</p>
                        <p class="text-xs text-gray-400 mt-1">ventes émises</p>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <p class="text-xs text-gray-500 mb-1">Recettes pharmacie</p>
                        <p class="text-3xl font-bold text-purple-600">{{ formatFCFA(stats.totalRecettes) }}</p>
                        <p class="text-xs text-gray-400 mt-1">période filtrée</p>
                    </div>
                </div>

                <!-- ===== Filtres ===== -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Du</label>
                            <input v-model="form.debut" @change="filtrer" type="date" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-purple-500 outline-none transition text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Au</label>
                            <input v-model="form.fin" @change="filtrer" type="date" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-purple-500 outline-none transition text-sm" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Recherche patient</label>
                            <div class="flex gap-2">
                                <input v-model="form.recherche" @keyup.enter="filtrer" type="text" placeholder="Nom ou prénom..." class="flex-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-purple-500 outline-none transition text-sm" />
                                <button @click="filtrer" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-semibold transition text-sm">Filtrer</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===== Tableau ===== -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">N°</th>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Date / Heure</th>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Patient</th>
                                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Lignes</th>
                                    <th v-if="user.role !== 'pharmacien'" class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Pharmacien</th>
                                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Total</th>
                                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-3">
                                        <span class="font-mono font-bold text-gray-900">{{ numeroFormate(ticket.numero) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="text-gray-900 font-medium">{{ formatDate(ticket.date_emission) }}</div>
                                        <div class="text-gray-500 text-xs">{{ formatHeure(ticket.emis_le) }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="font-medium text-gray-900">{{ ticket.patient_prenom }} {{ ticket.patient_nom }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm text-gray-700">
                                        {{ ticket.lines.length }}
                                    </td>
                                    <td v-if="user.role !== 'pharmacien'" class="px-4 py-3 text-sm text-gray-700">
                                        {{ ticket.user.name }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="font-semibold text-purple-600">{{ formatFCFA(ticket.total) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <Link :href="route('pharmacy.recu', ticket.id)" class="text-purple-600 hover:text-purple-700 text-sm font-medium">
                                            Voir reçu
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="tickets.data.length === 0">
                                    <td :colspan="user.role !== 'pharmacien' ? 7 : 6" class="px-4 py-12 text-center">
                                        <p class="text-gray-500 font-medium mb-1">Aucune vente trouvée</p>
                                        <p class="text-sm text-gray-400">Aucune vente pour cette période</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="tickets.last_page > 1" class="px-4 py-4 border-t border-gray-100 flex items-center justify-between flex-wrap gap-3">
                        <p class="text-sm text-gray-500">
                            Page {{ tickets.current_page }} sur {{ tickets.last_page }} · {{ tickets.total }} ventes
                        </p>
                        <div class="flex items-center gap-2">
                            <Link v-for="(link, i) in tickets.links" :key="i"
                                :href="link.url || '#'"
                                v-html="link.label"
                                preserve-scroll
                                class="px-3 py-1.5 text-sm rounded-lg transition"
                                :class="[link.active ? 'bg-purple-600 text-white' : 'bg-gray-50 text-gray-700 hover:bg-gray-100', !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : '']"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
