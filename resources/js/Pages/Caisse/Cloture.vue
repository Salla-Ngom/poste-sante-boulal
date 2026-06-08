<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    session: Object,
    totalVentes: Number,
    totalDepenses: Number,
    nombreTickets: Number,
    montantAttendu: Number,
});

const form = useForm({
    montant_compte: '',
});

const ecart = computed(() => {
    if (form.montant_compte === '') return null;
    return Number(form.montant_compte) - props.montantAttendu;
});

const couleurEcart = computed(() => {
    if (ecart.value === null) return 'text-gray-500';
    if (ecart.value === 0) return 'text-emerald-600';
    if (ecart.value > 0) return 'text-blue-600';
    return 'text-red-600';
});

const formatFCFA = (montant) => {
    return new Intl.NumberFormat('fr-FR').format(montant) + ' FCFA';
};

const submit = () => {
    form.post(route('caisse.cloturer'));
};
</script>

<template>
    <Head title="Clôture de caisse" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Clôture de caisse
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

                    <div class="bg-gradient-to-r from-purple-600 to-pink-600 p-6 text-white">
                        <h1 class="text-2xl font-bold mb-1">Clôturer la caisse</h1>
                        <p class="text-purple-100">Vérifiez les chiffres et saisissez le montant compté</p>
                    </div>

                    <div class="p-6 space-y-3">
                        <h3 class="font-semibold text-gray-900 mb-3">Récapitulatif de la session</h3>

                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-gray-600">Fond initial</span>
                            <span class="font-semibold text-gray-900">{{ formatFCFA(session.fond_caisse_initial) }}</span>
                        </div>

                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-gray-600">Tickets vendus</span>
                            <span class="font-semibold text-gray-900">{{ nombreTickets }} tickets</span>
                        </div>

                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-gray-600">Total des ventes</span>
                            <span class="font-semibold text-emerald-600">+ {{ formatFCFA(totalVentes) }}</span>
                        </div>

                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-gray-600">Total des dépenses</span>
                            <span class="font-semibold text-red-600">- {{ formatFCFA(totalDepenses) }}</span>
                        </div>

                        <div class="flex justify-between items-center py-3 bg-emerald-50 rounded-lg px-3 mt-3">
                            <span class="font-semibold text-emerald-900">Montant attendu en caisse</span>
                            <span class="font-bold text-emerald-700 text-lg">{{ formatFCFA(montantAttendu) }}</span>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">

                    <div>
                        <label for="montant" class="block text-sm font-medium text-gray-700 mb-2">
                            Montant compté en caisse (FCFA) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                id="montant"
                                v-model="form.montant_compte"
                                type="number"
                                step="1"
                                min="0"
                                required
                                autofocus
                                placeholder="Comptez et saisissez"
                                class="w-full px-4 py-3 pr-16 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition text-lg"
                            />
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-500">FCFA</span>
                        </div>
                        <p v-if="form.errors.montant_compte" class="mt-2 text-sm text-red-600">
                            {{ form.errors.montant_compte }}
                        </p>
                    </div>

                    <div v-if="ecart !== null" class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-600">Écart calculé</span>
                            <span class="font-bold text-xl" :class="couleurEcart">
                                {{ ecart > 0 ? '+' : '' }}{{ formatFCFA(ecart) }}
                            </span>
                        </div>
                        <p v-if="ecart === 0" class="text-sm text-emerald-600 mt-2">Caisse parfaitement équilibrée</p>
                        <p v-else-if="ecart > 0" class="text-sm text-blue-600 mt-2">Surplus dans la caisse</p>
                        <p v-else class="text-sm text-red-600 mt-2">Manquant dans la caisse</p>
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
                            class="flex-1 px-6 py-3 bg-purple-600 hover:bg-purple-700 disabled:bg-purple-400 text-white rounded-lg font-semibold shadow-lg shadow-purple-200 transition"
                        >
                            <span v-if="!form.processing">Confirmer la clôture</span>
                            <span v-else>Clôture en cours...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
