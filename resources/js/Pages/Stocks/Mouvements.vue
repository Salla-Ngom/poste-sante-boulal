<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    mouvements: Object,
    medicaments: Array,
    filtres: Object,
});

const form = useForm({
    debut: props.filtres.debut,
    fin: props.filtres.fin,
    medicament_id: props.filtres.medicament_id,
    type: props.filtres.type,
});

const filtrer = () => {
    form.get(route('stocks.mouvements'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatDateTime = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

const typeConfig = {
    entree: { libelle: 'Entrée', color: 'bg-emerald-100 text-emerald-700', sign: '+' },
    sortie_vente: { libelle: 'Vente', color: 'bg-blue-100 text-blue-700', sign: '-' },
    regularisation_positive: { libelle: 'Régul. +', color: 'bg-amber-100 text-amber-700', sign: '+' },
    regularisation_negative: { libelle: 'Régul. -', color: 'bg-red-100 text-red-700', sign: '-' },
};
</script>

<template>
    <Head title="Historique des mouvements" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between flex-wrap gap-3">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Historique des mouvements de stock
                </h2>
                <Link
                    :href="route('stocks.index')"
                    class="text-sm font-medium text-emerald-600 hover:text-emerald-700"
                >
                    ← Retour aux stocks
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

                <!-- ===== Filtres ===== -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Du</label>
                            <input v-model="form.debut" @change="filtrer" type="date" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 outline-none transition text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Au</label>
                            <input v-model="form.fin" @change="filtrer" type="date" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 outline-none transition text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Médicament</label>
                            <select v-model="form.medicament_id" @change="filtrer" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 outline-none transition text-sm">
                                <option value="">Tous</option>
                                <option v-for="med in medicaments" :key="med.id" :value="med.id">{{ med.libelle }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                            <select v-model="form.type" @change="filtrer" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 outline-none transition text-sm">
                                <option value="">Tous</option>
                                <option value="entree">Entrées</option>
                                <option value="sortie_vente">Ventes</option>
                                <option value="regularisation_positive">Régul. positives</option>
                                <option value="regularisation_negative">Régul. négatives</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- ===== Tableau ===== -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Date / Heure</th>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Médicament</th>
                                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Type</th>
                                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Quantité</th>
                                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Avant → Après</th>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Auteur</th>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Motif / Réf.</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="mvt in mouvements.data" :key="mvt.id" class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ formatDateTime(mvt.survenu_le) }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ mvt.medicament.libelle }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full" :class="typeConfig[mvt.type]?.color">
                                            {{ typeConfig[mvt.type]?.libelle }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="font-bold" :class="typeConfig[mvt.type]?.sign === '+' ? 'text-emerald-600' : 'text-red-600'">
                                            {{ typeConfig[mvt.type]?.sign }}{{ mvt.quantite }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm text-gray-600">
                                        {{ mvt.quantite_avant }} → <strong class="text-gray-900">{{ mvt.quantite_apres }}</strong>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ mvt.user.name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <div v-if="mvt.motif" class="italic">{{ mvt.motif }}</div>
                                        <div v-if="mvt.reference_externe" class="text-xs text-gray-500 mt-0.5">Réf : {{ mvt.reference_externe }}</div>
                                    </td>
                                </tr>
                                <tr v-if="mouvements.data.length === 0">
                                    <td colspan="7" class="px-4 py-12 text-center">
                                        <p class="text-gray-500 font-medium mb-1">Aucun mouvement</p>
                                        <p class="text-sm text-gray-400">Aucun mouvement pour cette période</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="mouvements.last_page > 1" class="px-4 py-4 border-t border-gray-100 flex items-center justify-between flex-wrap gap-3">
                        <p class="text-sm text-gray-500">
                            Page {{ mouvements.current_page }} sur {{ mouvements.last_page }} · {{ mouvements.total }} mouvements
                        </p>
                        <div class="flex items-center gap-2">
                            <Link v-for="(link, i) in mouvements.links" :key="i"
                                :href="link.url || '#'"
                                v-html="link.label"
                                preserve-scroll
                                class="px-3 py-1.5 text-sm rounded-lg transition"
                                :class="[link.active ? 'bg-emerald-600 text-white' : 'bg-gray-50 text-gray-700 hover:bg-gray-100', !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : '']"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
