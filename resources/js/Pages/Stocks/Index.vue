<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    medicaments: Array,
    stats: Object,
    filtres: Object,
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const peutReceptionner = computed(() => {
    return ['admin', 'pharmacien'].includes(user.value.role);
});

const peutRegulariser = computed(() => {
    return user.value.role === 'admin';
});

const formFiltres = useForm({
    recherche: props.filtres.recherche,
    filtre: props.filtres.filtre,
});

const filtrer = () => {
    formFiltres.get(route('stocks.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatFCFA = (montant) => {
    return new Intl.NumberFormat('fr-FR').format(montant || 0) + ' FCFA';
};

const badgeStock = (med) => {
    if (med.quantite_stock <= 0) return 'bg-red-100 text-red-700';
    if (med.quantite_stock <= med.seuil_alerte) return 'bg-amber-100 text-amber-700';
    return 'bg-emerald-100 text-emerald-700';
};

const libelleStock = (med) => {
    if (med.quantite_stock <= 0) return 'Rupture';
    if (med.quantite_stock <= med.seuil_alerte) return 'Alerte';
    return 'OK';
};

// ===== Modal Réception =====
const medicamentReception = ref(null);
const formReception = useForm({
    medicament_id: '',
    quantite: '',
    reference_externe: '',
    motif: '',
});

const ouvrirReception = (med) => {
    medicamentReception.value = med;
    formReception.medicament_id = med.id;
    formReception.quantite = '';
    formReception.reference_externe = '';
    formReception.motif = '';
    formReception.clearErrors();
};

const fermerReception = () => {
    medicamentReception.value = null;
    formReception.reset();
};

const confirmerReception = () => {
    formReception.post(route('stocks.receptionner'), {
        preserveScroll: true,
        onSuccess: () => fermerReception(),
    });
};

// ===== Modal Régularisation =====
const medicamentRegul = ref(null);
const formRegul = useForm({
    medicament_id: '',
    nouvelle_quantite: '',
    motif: '',
});

const ouvrirRegul = (med) => {
    medicamentRegul.value = med;
    formRegul.medicament_id = med.id;
    formRegul.nouvelle_quantite = med.quantite_stock;
    formRegul.motif = '';
    formRegul.clearErrors();
};

const fermerRegul = () => {
    medicamentRegul.value = null;
    formRegul.reset();
};

const confirmerRegul = () => {
    formRegul.post(route('stocks.regulariser'), {
        preserveScroll: true,
        onSuccess: () => fermerRegul(),
    });
};

const differenceRegul = computed(() => {
    if (!medicamentRegul.value || formRegul.nouvelle_quantite === '') return null;
    return Number(formRegul.nouvelle_quantite) - medicamentRegul.value.quantite_stock;
});
</script>

<template>
    <Head title="Gestion du stock" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between flex-wrap gap-3">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Gestion du stock
                </h2>
                <Link
                    :href="route('stocks.mouvements')"
                    class="text-sm font-medium text-emerald-600 hover:text-emerald-700"
                >
                    Voir l'historique des mouvements →
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- ===== Stats ===== -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <p class="text-xs text-gray-500 mb-1">Médicaments actifs</p>
                        <p class="text-3xl font-bold text-gray-900">{{ stats.total }}</p>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <p class="text-xs text-gray-500 mb-1">En alerte</p>
                        <p class="text-3xl font-bold text-amber-600">{{ stats.alerte }}</p>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <p class="text-xs text-gray-500 mb-1">En rupture</p>
                        <p class="text-3xl font-bold text-red-600">{{ stats.rupture }}</p>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <p class="text-xs text-gray-500 mb-1">Valorisation stock</p>
                        <p class="text-2xl font-bold text-emerald-600">{{ formatFCFA(stats.valorisation) }}</p>
                    </div>
                </div>

                <!-- ===== Bandeau info selon rôle ===== -->
                <div v-if="user.role === 'pharmacien'" class="bg-purple-50 border border-purple-200 rounded-xl p-4">
                    <p class="text-sm text-purple-900">
                        <span class="font-semibold">Pharmacien :</span>
                        Vous pouvez <strong>réceptionner</strong> du stock à chaque livraison fournisseur.
                        Les <em>régularisations</em> (corrections d'écart) sont réservées à l'administrateur.
                    </p>
                </div>
                <div v-else-if="user.role === 'superviseur'" class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <p class="text-sm text-blue-900">
                        <span class="font-semibold">Superviseur :</span>
                        Vous êtes en mode lecture seule. Consultez les stocks et l'historique des mouvements.
                    </p>
                </div>

                <!-- ===== Filtres ===== -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="flex-1">
                            <input
                                v-model="formFiltres.recherche"
                                @keyup.enter="filtrer"
                                type="text"
                                placeholder="Rechercher un médicament..."
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 outline-none transition text-sm"
                            />
                        </div>
                        <select
                            v-model="formFiltres.filtre"
                            @change="filtrer"
                            class="px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 outline-none transition text-sm"
                        >
                            <option value="tous">Tous</option>
                            <option value="ok">Stock OK</option>
                            <option value="alerte">En alerte</option>
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
                                    <th class="text-right px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="med in medicaments" :key="med.id" class="hover:bg-gray-50 transition">
                                    <td class="px-5 py-4">
                                        <div class="font-medium text-gray-900">{{ med.libelle }}</div>
                                        <div v-if="med.forme_conditionnement" class="text-xs text-gray-500 mt-0.5">
                                            {{ med.forme_conditionnement }}
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <span class="text-sm text-gray-700">{{ formatFCFA(med.prix) }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span class="text-2xl font-bold" :class="med.quantite_stock <= 0 ? 'text-red-600' : (med.quantite_stock <= med.seuil_alerte ? 'text-amber-600' : 'text-gray-900')">
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
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <button
                                                v-if="peutReceptionner"
                                                @click="ouvrirReception(med)"
                                                class="text-emerald-600 hover:text-emerald-700 text-sm font-medium"
                                            >
                                                + Réceptionner
                                            </button>
                                            <button
                                                v-if="peutRegulariser"
                                                @click="ouvrirRegul(med)"
                                                class="text-amber-600 hover:text-amber-700 text-sm font-medium"
                                            >
                                                Régulariser
                                            </button>
                                            <span v-if="!peutReceptionner && !peutRegulariser" class="text-xs text-gray-400 italic">
                                                Lecture seule
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="medicaments.length === 0">
                                    <td colspan="6" class="px-5 py-12 text-center">
                                        <p class="text-gray-500 font-medium mb-1">Aucun médicament</p>
                                        <p class="text-sm text-gray-400">Ajustez vos filtres ou créez un médicament dans le catalogue.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== Modal Réception ===== -->
        <div v-if="medicamentReception" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
                <div class="bg-gradient-to-r from-emerald-600 to-teal-600 p-5 text-white">
                    <h3 class="text-lg font-bold">Réception de stock</h3>
                    <p class="text-sm text-emerald-100 mt-1">{{ medicamentReception.libelle }}</p>
                </div>
                <form @submit.prevent="confirmerReception" class="p-5 space-y-4">

                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Stock actuel</span>
                            <span class="font-bold text-gray-900">{{ medicamentReception.quantite_stock }} unités</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Quantité reçue <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="formReception.quantite"
                            type="number"
                            min="1"
                            required
                            placeholder="Ex: 50"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 outline-none transition font-semibold text-lg"
                        />
                        <p v-if="formReception.errors.quantite" class="mt-1 text-sm text-red-600">{{ formReception.errors.quantite }}</p>
                        <p v-if="formReception.quantite > 0" class="text-xs text-emerald-700 mt-1">
                            Nouveau stock après réception : <strong>{{ medicamentReception.quantite_stock + Number(formReception.quantite) }}</strong> unités
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            N° bon de livraison
                        </label>
                        <input
                            v-model="formReception.reference_externe"
                            type="text"
                            placeholder="Ex: BL-2026-042"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 outline-none transition"
                        />
                        <p class="text-xs text-gray-500 mt-1">Optionnel. Pour traçabilité.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Note
                        </label>
                        <input
                            v-model="formReception.motif"
                            type="text"
                            placeholder="Ex: Livraison du grossiste X"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 outline-none transition"
                        />
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="fermerReception" class="flex-1 px-4 py-2 bg-white hover:bg-gray-100 border border-gray-200 text-gray-700 rounded-lg font-medium transition text-sm">
                            Annuler
                        </button>
                        <button type="submit" :disabled="formReception.processing" class="flex-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-400 text-white rounded-lg font-semibold transition text-sm">
                            <span v-if="!formReception.processing">Valider la réception</span>
                            <span v-else>Enregistrement...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ===== Modal Régularisation ===== -->
        <div v-if="medicamentRegul" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
                <div class="bg-gradient-to-r from-amber-600 to-orange-600 p-5 text-white">
                    <h3 class="text-lg font-bold">Régulariser le stock</h3>
                    <p class="text-sm text-amber-100 mt-1">{{ medicamentRegul.libelle }}</p>
                </div>
                <form @submit.prevent="confirmerRegul" class="p-5 space-y-4">

                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                        <p class="text-xs text-amber-900">
                            <span class="font-semibold">Attention :</span> La régularisation modifie le stock en dehors d'une vente ou réception. Cette action est tracée et le motif est obligatoire.
                        </p>
                    </div>

                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Stock actuel système</span>
                            <span class="font-bold text-gray-900">{{ medicamentRegul.quantite_stock }} unités</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Nouvelle quantité réelle <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="formRegul.nouvelle_quantite"
                            type="number"
                            min="0"
                            required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 outline-none transition font-semibold text-lg"
                        />
                        <p v-if="formRegul.errors.nouvelle_quantite" class="mt-1 text-sm text-red-600">{{ formRegul.errors.nouvelle_quantite }}</p>
                        <p v-if="differenceRegul !== null && differenceRegul !== 0" class="text-xs mt-1" :class="differenceRegul > 0 ? 'text-emerald-700' : 'text-red-700'">
                            Variation : <strong>{{ differenceRegul > 0 ? '+' : '' }}{{ differenceRegul }}</strong> unités
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Motif <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            v-model="formRegul.motif"
                            rows="3"
                            required
                            placeholder="Ex: Inventaire physique du 10/05/2026 : écart de 5 unités, casse constatée"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 outline-none transition resize-none"
                        ></textarea>
                        <p class="text-xs text-gray-500 mt-1">Minimum 5 caractères. Pour traçabilité dans le journal d'audit.</p>
                        <p v-if="formRegul.errors.motif" class="mt-1 text-sm text-red-600">{{ formRegul.errors.motif }}</p>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="fermerRegul" class="flex-1 px-4 py-2 bg-white hover:bg-gray-100 border border-gray-200 text-gray-700 rounded-lg font-medium transition text-sm">
                            Annuler
                        </button>
                        <button type="submit" :disabled="formRegul.processing" class="flex-1 px-4 py-2 bg-amber-600 hover:bg-amber-700 disabled:bg-amber-400 text-white rounded-lg font-semibold transition text-sm">
                            <span v-if="!formRegul.processing">Valider la régularisation</span>
                            <span v-else>Enregistrement...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
