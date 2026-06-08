<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    medicaments: Array,
    stats: Object,
    filtres: Object,
});

const form = useForm({
    recherche: props.filtres.recherche,
    filtre: props.filtres.filtre,
});

const filtrer = () => {
    form.get(route('medicaments.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatFCFA = (montant) => {
    return new Intl.NumberFormat('fr-FR').format(montant || 0) + ' FCFA';
};

// Modal de toggle
const medicamentATogglet = ref(null);

const ouvrirToggle = (medicament) => {
    medicamentATogglet.value = medicament;
};

const fermerToggle = () => {
    medicamentATogglet.value = null;
};

const confirmerToggle = () => {
    router.post(route('medicaments.toggle', medicamentATogglet.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => fermerToggle(),
    });
};

// Couleur du badge selon le stock
const badgeStock = (medicament) => {
    if (medicament.quantite_stock <= 0) {
        return 'bg-red-100 text-red-700';
    }
    if (medicament.quantite_stock <= medicament.seuil_alerte) {
        return 'bg-amber-100 text-amber-700';
    }
    return 'bg-emerald-100 text-emerald-700';
};

const libelleStock = (medicament) => {
    if (medicament.quantite_stock <= 0) return 'Rupture';
    if (medicament.quantite_stock <= medicament.seuil_alerte) return 'Alerte';
    return 'OK';
};
</script>

<template>
    <Head title="Médicaments" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Catalogue des médicaments
                </h2>
                <Link
                    :href="route('medicaments.create')"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-semibold shadow-md shadow-emerald-200 transition flex items-center gap-2"
                >
                    <span class="text-lg leading-none">+</span>
                    Nouveau médicament
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- ===== Stats KPIs ===== -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <p class="text-xs text-gray-500 mb-1">Total catalogue</p>
                        <p class="text-3xl font-bold text-gray-900">{{ stats.total }}</p>
                        <p class="text-xs text-gray-400 mt-1">médicaments</p>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <p class="text-xs text-gray-500 mb-1">Actifs</p>
                        <p class="text-3xl font-bold text-emerald-600">{{ stats.actifs }}</p>
                        <p class="text-xs text-gray-400 mt-1">disponibles</p>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <p class="text-xs text-gray-500 mb-1">En alerte</p>
                        <p class="text-3xl font-bold text-amber-600">{{ stats.alerte }}</p>
                        <p class="text-xs text-gray-400 mt-1">stock bas</p>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <p class="text-xs text-gray-500 mb-1">En rupture</p>
                        <p class="text-3xl font-bold text-red-600">{{ stats.rupture }}</p>
                        <p class="text-xs text-gray-400 mt-1">à réapprovisionner</p>
                    </div>
                </div>

                <!-- ===== Filtres ===== -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="flex-1">
                            <input
                                v-model="form.recherche"
                                @keyup.enter="filtrer"
                                type="text"
                                placeholder="Rechercher par nom..."
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition text-sm"
                            />
                        </div>
                        <select
                            v-model="form.filtre"
                            @change="filtrer"
                            class="px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 outline-none transition text-sm"
                        >
                            <option value="tous">Tous</option>
                            <option value="actifs">Actifs uniquement</option>
                            <option value="alerte">En alerte de stock</option>
                            <option value="rupture">En rupture</option>
                        </select>
                        <button
                            @click="filtrer"
                            class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-semibold transition text-sm"
                        >
                            Filtrer
                        </button>
                    </div>
                </div>

                <!-- ===== Tableau ===== -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Médicament</th>
                                    <th class="text-right px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Prix</th>
                                    <th class="text-center px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Stock</th>
                                    <th class="text-center px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Seuil</th>
                                    <th class="text-center px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">État</th>
                                    <th class="text-center px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Statut</th>
                                    <th class="text-right px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="med in medicaments" :key="med.id" class="hover:bg-gray-50 transition" :class="{ 'opacity-60': !med.actif }">
                                    <td class="px-5 py-4">
                                        <div class="font-medium text-gray-900">{{ med.libelle }}</div>
                                        <div v-if="med.forme_conditionnement" class="text-xs text-gray-500 mt-0.5">
                                            {{ med.forme_conditionnement }}
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <span class="font-semibold text-emerald-600">{{ formatFCFA(med.prix) }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span class="font-bold text-lg" :class="med.quantite_stock <= 0 ? 'text-red-600' : (med.quantite_stock <= med.seuil_alerte ? 'text-amber-600' : 'text-gray-900')">
                                            {{ med.quantite_stock }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-center text-sm text-gray-600">
                                        {{ med.seuil_alerte }}
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span class="inline-block px-2.5 py-1 text-xs font-semibold rounded-full" :class="badgeStock(med)">
                                            {{ libelleStock(med) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span v-if="med.actif" class="inline-block px-2.5 py-1 bg-emerald-100 text-emerald-700 text-xs font-semibold rounded-full">
                                            Actif
                                        </span>
                                        <span v-else class="inline-block px-2.5 py-1 bg-gray-100 text-gray-600 text-xs font-semibold rounded-full">
                                            Désactivé
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <Link
                                                :href="route('medicaments.edit', med.id)"
                                                class="text-emerald-600 hover:text-emerald-700 text-sm font-medium"
                                            >
                                                Modifier
                                            </Link>
                                            <button
                                                @click="ouvrirToggle(med)"
                                                class="text-sm font-medium"
                                                :class="med.actif ? 'text-red-600 hover:text-red-700' : 'text-blue-600 hover:text-blue-700'"
                                            >
                                                {{ med.actif ? 'Désactiver' : 'Réactiver' }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="medicaments.length === 0">
                                    <td colspan="7" class="px-5 py-12 text-center">
                                        <p class="text-gray-500 font-medium mb-1">Aucun médicament</p>
                                        <p class="text-sm text-gray-400">Créez votre premier médicament avec le bouton en haut</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal de toggle -->
        <div v-if="medicamentATogglet" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                        {{ medicamentATogglet.actif ? 'Désactiver' : 'Réactiver' }} ce médicament ?
                    </h3>
                    <p class="text-sm text-gray-600 mb-4">
                        <span class="font-semibold">{{ medicamentATogglet.libelle }}</span>
                        <span v-if="medicamentATogglet.actif">
                            ne sera plus disponible pour la vente. Le stock actuel ({{ medicamentATogglet.quantite_stock }}) reste intact.
                        </span>
                        <span v-else>
                            redeviendra disponible pour la vente.
                        </span>
                    </p>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex gap-3">
                    <button @click="fermerToggle" class="flex-1 px-4 py-2 bg-white hover:bg-gray-100 border border-gray-200 text-gray-700 rounded-lg font-medium transition text-sm">
                        Annuler
                    </button>
                    <button @click="confirmerToggle" class="flex-1 px-4 py-2 text-white rounded-lg font-semibold transition text-sm" :class="medicamentATogglet.actif ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700'">
                        Confirmer
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
