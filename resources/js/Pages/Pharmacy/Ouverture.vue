<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    fond_caisse_initial: '',
});

const submit = () => {
    form.post(route('pharmacy.caisse.store'));
};

const formatFCFA = (montant) => {
    return new Intl.NumberFormat('fr-FR').format(montant || 0) + ' FCFA';
};
</script>

<template>
    <Head title="Ouverture caisse pharmacie" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Ouverture de la caisse Pharmacie
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

                    <div class="bg-gradient-to-r from-purple-600 to-pink-600 p-6 text-white">
                        <h1 class="text-2xl font-bold mb-1">Ouvrir la caisse Pharmacie</h1>
                        <p class="text-purple-100">Saisissez le fond de caisse initial pour démarrer votre journée</p>
                    </div>

                    <form @submit.prevent="submit" class="p-6 space-y-5">

                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                            <p class="text-sm text-purple-900">
                                <span class="font-semibold">Pharmacien :</span>
                                Cette caisse est <strong>indépendante</strong> de la caisse Accueil. Elle suit les ventes de médicaments uniquement.
                            </p>
                        </div>

                        <div>
                            <label for="fond" class="block text-sm font-medium text-gray-700 mb-2">
                                Fond de caisse initial <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    id="fond"
                                    v-model="form.fond_caisse_initial"
                                    type="number"
                                    step="100"
                                    min="0"
                                    required
                                    autofocus
                                    placeholder="0"
                                    class="w-full px-4 py-3 pr-16 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-purple-500 focus:ring-2 focus:ring-purple-200 outline-none transition text-2xl font-bold"
                                />
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-500">FCFA</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Montant en espèces présent dans la caisse au début de la journée.</p>
                            <p v-if="form.errors.fond_caisse_initial" class="mt-1 text-sm text-red-600">{{ form.errors.fond_caisse_initial }}</p>

                            <div v-if="form.fond_caisse_initial > 0" class="mt-3 p-3 bg-purple-50 border border-purple-200 rounded-lg">
                                <p class="text-sm text-purple-800">Fond saisi : <span class="font-bold">{{ formatFCFA(form.fond_caisse_initial) }}</span></p>
                            </div>
                        </div>

                        <div class="flex gap-3 pt-4 border-t border-gray-100">
                            <Link
                                :href="route('dashboard')"
                                class="flex-1 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 text-center rounded-lg font-medium transition"
                            >
                                Annuler
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing || !form.fond_caisse_initial"
                                class="flex-1 px-6 py-3 bg-purple-600 hover:bg-purple-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-lg font-semibold shadow-lg shadow-purple-200 transition"
                            >
                                <span v-if="!form.processing">Ouvrir la caisse</span>
                                <span v-else>Ouverture...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
