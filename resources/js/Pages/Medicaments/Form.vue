<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    medicament: Object,
});

const isEdit = computed(() => !!props.medicament);

const form = useForm({
    libelle: props.medicament?.libelle || '',
    forme_conditionnement: props.medicament?.forme_conditionnement || '',
    prix: props.medicament?.prix || '',
    quantite_stock: isEdit.value ? null : 0, // pas modifiable en édition
    seuil_alerte: props.medicament?.seuil_alerte || 10,
    actif: props.medicament?.actif ?? true,
});

const submit = () => {
    if (isEdit.value) {
        form.put(route('medicaments.update', props.medicament.id));
    } else {
        form.post(route('medicaments.store'));
    }
};

const formatFCFA = (montant) => {
    return new Intl.NumberFormat('fr-FR').format(montant || 0) + ' FCFA';
};
</script>

<template>
    <Head :title="isEdit ? 'Modifier le médicament' : 'Nouveau médicament'" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ isEdit ? 'Modifier le médicament' : 'Nouveau médicament' }}
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

                    <div class="bg-gradient-to-r from-emerald-600 to-teal-600 p-6 text-white">
                        <h1 class="text-2xl font-bold mb-1">
                            {{ isEdit ? 'Modifier' : 'Créer' }} un médicament
                        </h1>
                        <p class="text-emerald-100">
                            {{ isEdit ? 'Modifiez les informations du médicament' : 'Ajoutez un nouveau médicament au catalogue' }}
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="p-6 space-y-5">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Libellé <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.libelle"
                                type="text"
                                required
                                autofocus
                                placeholder="Ex: Paracétamol 500mg"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition"
                            />
                            <p v-if="form.errors.libelle" class="mt-1 text-sm text-red-600">{{ form.errors.libelle }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Forme et conditionnement
                            </label>
                            <input
                                v-model="form.forme_conditionnement"
                                type="text"
                                placeholder="Ex: Comprimé, boîte de 20"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition"
                            />
                            <p class="text-xs text-gray-500 mt-1">Optionnel. Ex: « Comprimé, boîte de 20 », « Sirop 100ml »</p>
                            <p v-if="form.errors.forme_conditionnement" class="mt-1 text-sm text-red-600">{{ form.errors.forme_conditionnement }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Prix de vente unitaire <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.prix"
                                    type="number"
                                    step="1"
                                    min="0"
                                    required
                                    placeholder="500"
                                    class="w-full px-4 py-2.5 pr-16 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition text-lg font-semibold"
                                />
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-500">FCFA</span>
                            </div>
                            <p v-if="form.errors.prix" class="mt-1 text-sm text-red-600">{{ form.errors.prix }}</p>
                            <div v-if="form.prix > 0" class="mt-2 p-2 bg-emerald-50 border border-emerald-200 rounded-lg">
                                <p class="text-sm text-emerald-800">Aperçu : <span class="font-bold">{{ formatFCFA(form.prix) }}</span></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div v-if="!isEdit">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Stock initial <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.quantite_stock"
                                    type="number"
                                    min="0"
                                    required
                                    placeholder="0"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition font-semibold"
                                />
                                <p class="text-xs text-gray-500 mt-1">Quantité disponible immédiatement.</p>
                                <p v-if="form.errors.quantite_stock" class="mt-1 text-sm text-red-600">{{ form.errors.quantite_stock }}</p>
                            </div>
                            <div v-else class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <p class="text-xs text-blue-700 font-semibold uppercase tracking-wider mb-1">Stock actuel</p>
                                <p class="text-2xl font-bold text-gray-900">{{ medicament.quantite_stock }}</p>
                                <p class="text-xs text-blue-700 mt-1">Modifiez le stock via la page Stocks.</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Seuil d'alerte <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.seuil_alerte"
                                    type="number"
                                    min="0"
                                    required
                                    placeholder="10"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition font-semibold"
                                />
                                <p class="text-xs text-gray-500 mt-1">Alerte si stock ≤ ce nombre.</p>
                                <p v-if="form.errors.seuil_alerte" class="mt-1 text-sm text-red-600">{{ form.errors.seuil_alerte }}</p>
                            </div>
                        </div>

                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input
                                    v-model="form.actif"
                                    type="checkbox"
                                    class="mt-0.5 w-5 h-5 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500"
                                />
                                <div>
                                    <span class="block font-medium text-gray-900">Médicament actif</span>
                                    <span class="block text-sm text-gray-600 mt-0.5">
                                        Si décoché, le médicament n'apparaîtra plus dans la liste de vente.
                                    </span>
                                </div>
                            </label>
                        </div>

                        <div class="flex gap-3 pt-4 border-t border-gray-100">
                            <Link
                                :href="route('medicaments.index')"
                                class="flex-1 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 text-center rounded-lg font-medium transition"
                            >
                                Annuler
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex-1 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-400 text-white rounded-lg font-semibold shadow-lg shadow-emerald-200 transition"
                            >
                                <span v-if="!form.processing">{{ isEdit ? 'Enregistrer' : 'Créer le médicament' }}</span>
                                <span v-else>Enregistrement...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
