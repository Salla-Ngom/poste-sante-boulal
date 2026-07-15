<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    session: Object,
    medicaments: Array,
});

// === État local : panier ===
const panier = ref([]); // [{ medicament_id, libelle, prix, quantite, stock_max }]
const recherche = ref('');

// === Saisie patient + envoi backend ===
const form = useForm({
    patient_nom: '',
    patient_prenom: '',
    est_cmu: false,
    lignes: [],
});

// === Recherche filtrée ===
const medicamentsFiltres = computed(() => {
    if (!recherche.value) {
        return props.medicaments.filter(m => m.quantite_stock > 0);
    }
    const term = recherche.value.toLowerCase();
    return props.medicaments.filter(m =>
        m.quantite_stock > 0 && m.libelle.toLowerCase().includes(term)
    );
});

// === Total panier ===
const totalPanier = computed(() => {
    return panier.value.reduce((sum, ligne) => sum + (ligne.prix * ligne.quantite), 0);
});

const nombreArticles = computed(() => {
    return panier.value.reduce((sum, ligne) => sum + ligne.quantite, 0);
});

// === Actions panier ===
const ajouterAuPanier = (medicament) => {
    const existant = panier.value.find(l => l.medicament_id === medicament.id);
    if (existant) {
        if (existant.quantite < medicament.quantite_stock) {
            existant.quantite++;
        } else {
            alert(`Stock maximum atteint pour ${medicament.libelle} (${medicament.quantite_stock} disponibles).`);
        }
    } else {
        panier.value.push({
            medicament_id: medicament.id,
            libelle: medicament.libelle,
            forme: medicament.forme_conditionnement,
            prix: Number(medicament.prix),
            quantite: 1,
            stock_max: medicament.quantite_stock,
        });
    }
};

const incrementer = (ligne) => {
    if (ligne.quantite < ligne.stock_max) {
        ligne.quantite++;
    }
};

const decrementer = (ligne) => {
    if (ligne.quantite > 1) {
        ligne.quantite--;
    }
};

const retirer = (index) => {
    panier.value.splice(index, 1);
};

const viderPanier = () => {
    if (confirm('Vider le panier ? Tout sera supprimé.')) {
        panier.value = [];
    }
};

// === Soumission ===
const peutValider = computed(() => {
    return panier.value.length > 0
        && form.patient_nom.trim() !== ''
        && form.patient_prenom.trim() !== '';
});

const submit = () => {
    form.lignes = panier.value.map(l => ({
        medicament_id: l.medicament_id,
        quantite: l.quantite,
    }));
    form.post(route('pharmacy.store'));
};

// === Utilitaires ===
const formatFCFA = (montant) => {
    return new Intl.NumberFormat('fr-FR').format(montant || 0) + ' FCFA';
};

const badgeStock = (stock) => {
    if (stock <= 0) return 'bg-red-100 text-red-700';
    if (stock <= 10) return 'bg-amber-100 text-amber-700';
    return 'bg-emerald-100 text-emerald-700';
};
</script>

<template>
    <Head title="Vendre des médicaments" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Vente pharmacie
            </h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- ===== COLONNE GAUCHE : Catalogue médicaments ===== -->
                    <div class="lg:col-span-2 space-y-4">

                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="p-4 border-b border-gray-100">
                                <h3 class="font-semibold text-gray-900 mb-3">Médicaments disponibles</h3>
                                <input
                                    v-model="recherche"
                                    type="text"
                                    placeholder="Rechercher un médicament..."
                                    class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-purple-500 focus:ring-2 focus:ring-purple-200 outline-none transition text-sm"
                                />
                            </div>

                            <div class="max-h-[600px] overflow-y-auto">
                                <div v-if="medicamentsFiltres.length === 0" class="p-12 text-center">
                                    <p class="text-gray-500 font-medium">Aucun médicament trouvé</p>
                                    <p class="text-sm text-gray-400 mt-1">Vérifiez votre recherche ou contactez l'admin pour le catalogue</p>
                                </div>

                                <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-4">
                                    <button
                                        v-for="med in medicamentsFiltres"
                                        :key="med.id"
                                        @click="ajouterAuPanier(med)"
                                        class="text-left p-3 bg-white border-2 border-gray-200 rounded-xl hover:border-purple-300 hover:shadow-md transition group"
                                    >
                                        <div class="flex items-start justify-between gap-2 mb-2">
                                            <div class="flex-1 min-w-0">
                                                <p class="font-semibold text-gray-900 group-hover:text-purple-700 transition truncate">{{ med.libelle }}</p>
                                                <p v-if="med.forme_conditionnement" class="text-xs text-gray-500 mt-0.5 truncate">
                                                    {{ med.forme_conditionnement }}
                                                </p>
                                            </div>
                                            <span class="inline-block px-2 py-0.5 text-xs font-semibold rounded-full flex-shrink-0" :class="badgeStock(med.quantite_stock)">
                                                {{ med.quantite_stock }}
                                            </span>
                                        </div>
                                        <p class="text-lg font-bold text-purple-600">{{ formatFCFA(med.prix) }}</p>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== COLONNE DROITE : Panier ===== -->
                    <div class="space-y-4">

                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 sticky top-4">

                            <div class="bg-gradient-to-r from-purple-600 to-pink-600 p-4 text-white rounded-t-2xl">
                                <div class="flex items-center justify-between">
                                    <h3 class="font-bold">Panier</h3>
                                    <span v-if="nombreArticles > 0" class="bg-white/20 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                                        {{ nombreArticles }} article{{ nombreArticles > 1 ? 's' : '' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Lignes du panier -->
                            <div class="max-h-[300px] overflow-y-auto">
                                <div v-if="panier.length === 0" class="p-8 text-center">
                                    <p class="text-gray-400 text-sm">Cliquez sur un médicament<br>pour l'ajouter au panier</p>
                                </div>

                                <ul v-else class="divide-y divide-gray-100">
                                    <li v-for="(ligne, index) in panier" :key="ligne.medicament_id" class="p-3">
                                        <div class="flex items-start justify-between gap-2 mb-2">
                                            <div class="flex-1 min-w-0">
                                                <p class="font-medium text-sm text-gray-900 truncate">{{ ligne.libelle }}</p>
                                                <p class="text-xs text-gray-500">{{ formatFCFA(ligne.prix) }} l'unité</p>
                                            </div>
                                            <button @click="retirer(index)" class="text-red-500 hover:text-red-700 text-xs font-medium flex-shrink-0">
                                                Retirer
                                            </button>
                                        </div>

                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <button @click="decrementer(ligne)" class="w-7 h-7 bg-gray-100 hover:bg-gray-200 rounded-lg font-bold text-gray-700 transition">−</button>
                                                <span class="font-bold text-gray-900 w-8 text-center">{{ ligne.quantite }}</span>
                                                <button @click="incrementer(ligne)" :disabled="ligne.quantite >= ligne.stock_max" class="w-7 h-7 bg-gray-100 hover:bg-gray-200 disabled:opacity-40 disabled:cursor-not-allowed rounded-lg font-bold text-gray-700 transition">+</button>
                                            </div>
                                            <p class="font-bold text-emerald-600 text-sm">{{ formatFCFA(ligne.prix * ligne.quantite) }}</p>
                                        </div>
                                    </li>
                                </ul>
                            </div>

                            <!-- Saisie patient -->
                            <div v-if="panier.length > 0" class="p-4 border-t border-gray-100 bg-gray-50 space-y-3">
                                <p class="text-xs font-semibold text-gray-700 uppercase tracking-wider">Patient</p>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <input
                                            v-model="form.patient_prenom"
                                            type="text"
                                            placeholder="Prénom *"
                                            required
                                            class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg focus:border-purple-500 focus:ring-2 focus:ring-purple-200 outline-none transition text-sm"
                                        />
                                        <p v-if="form.errors.patient_prenom" class="mt-1 text-xs text-red-600">{{ form.errors.patient_prenom }}</p>
                                    </div>
                                    <div>
                                        <input
                                            v-model="form.patient_nom"
                                            type="text"
                                            placeholder="Nom *"
                                            required
                                            class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg focus:border-purple-500 focus:ring-2 focus:ring-purple-200 outline-none transition text-sm"
                                        />
                                        <p v-if="form.errors.patient_nom" class="mt-1 text-xs text-red-600">{{ form.errors.patient_nom }}</p>
                                    </div>
                                </div>

                                <!-- Prise en charge CMU -->
                                <label class="mt-3 flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition"
                                    :class="form.est_cmu ? 'bg-blue-50 border-blue-300' : 'bg-gray-50 border-gray-200'">
                                    <input
                                        type="checkbox"
                                        v-model="form.est_cmu"
                                        class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="text-sm font-medium" :class="form.est_cmu ? 'text-blue-800' : 'text-gray-700'">
                                        Prise en charge CMU
                                        <span class="block text-xs font-normal" :class="form.est_cmu ? 'text-blue-600' : 'text-gray-500'">
                                            Le patient ne paie rien — la vente n'entre pas en caisse
                                        </span>
                                    </span>
                                </label>
                            </div>

                            <!-- Total + actions -->
                            <div v-if="panier.length > 0" class="p-4 border-t-2 border-purple-200 bg-gradient-to-br from-purple-50 to-pink-50">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-medium text-gray-700">Total</span>
                                    <span class="text-2xl font-bold text-purple-700">{{ formatFCFA(totalPanier) }}</span>
                                </div>
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-xs font-medium" :class="form.est_cmu ? 'text-blue-700' : 'text-gray-500'">À encaisser</span>
                                    <span class="text-sm font-bold" :class="form.est_cmu ? 'text-blue-700' : 'text-gray-700'">
                                        {{ form.est_cmu ? '0 FCFA (CMU)' : formatFCFA(totalPanier) }}
                                    </span>
                                </div>

                                <div class="space-y-2">
                                    <button
                                        @click="submit"
                                        :disabled="!peutValider || form.processing"
                                        class="w-full px-4 py-3 bg-purple-600 hover:bg-purple-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-lg font-semibold shadow-md transition"
                                    >
                                        <span v-if="!form.processing">Valider la vente</span>
                                        <span v-else>Enregistrement...</span>
                                    </button>
                                    <button
                                        @click="viderPanier"
                                        class="w-full px-4 py-2 bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 rounded-lg font-medium transition text-sm"
                                    >
                                        Vider le panier
                                    </button>
                                </div>

                                <p v-if="!peutValider" class="text-xs text-amber-700 mt-3 text-center">
                                    ⓘ Saisissez le nom et le prénom du patient pour valider
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
