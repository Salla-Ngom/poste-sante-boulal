<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    fond_caisse_initial: '',
});

const submit = () => {
    form.post(route('caisse.store'));
};
</script>

<template>
    <Head title="Ouverture de caisse" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Ouverture de caisse
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

                    <div class="bg-gradient-to-r from-emerald-600 to-teal-600 p-6 text-white">
                        <h1 class="text-2xl font-bold mb-1">Ouvrir ma caisse</h1>
                        <p class="text-emerald-100">Saisissez le fond initial pour démarrer une nouvelle session</p>
                    </div>

                    <form @submit.prevent="submit" class="p-6 space-y-6">

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <p class="text-sm text-blue-900">
                                <span class="font-semibold">Fond de caisse</span> = montant en espèces que vous avez dans le tiroir au début de votre session. Comptez bien avant de saisir.
                            </p>
                        </div>

                        <div>
                            <label for="fond" class="block text-sm font-medium text-gray-700 mb-2">
                                Fond de caisse initial (FCFA) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    id="fond"
                                    v-model="form.fond_caisse_initial"
                                    type="number"
                                    step="1"
                                    min="0"
                                    required
                                    autofocus
                                    placeholder="Ex: 10000"
                                    class="w-full px-4 py-3 pr-16 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition text-lg"
                                />
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-500">
                                    FCFA
                                </span>
                            </div>
                            <p v-if="form.errors.fond_caisse_initial" class="mt-2 text-sm text-red-600">
                                {{ form.errors.fond_caisse_initial }}
                            </p>
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
                                :disabled="form.processing"
                                class="flex-1 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-400 text-white rounded-lg font-semibold shadow-lg shadow-emerald-200 transition"
                            >
                                <span v-if="!form.processing">Ouvrir la caisse</span>
                                <span v-else>Ouverture en cours...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
