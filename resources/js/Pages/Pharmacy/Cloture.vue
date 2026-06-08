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

const submit = () => {
    if (!confirm('Confirmer la clôture de la caisse Pharmacie ? Cette action est définitive.')) return;
    form.post(route('pharmacy.caisse.cloturer'));
};

const formatFCFA = (montant) => {
    return new Intl.NumberFormat('fr-FR').format(montant || 0) + ' FCFA';
};

const formatHeure = (dateString) => {
    return new Date(dateString).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <Head title="Clôture caisse pharmacie" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Clôture de la caisse Pharmacie
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

                    <div class="bg-gradient-to-r from-purple-600 to-pink-600 p-6 text-white">
                        <h1 class="text-2xl font-bold mb-1">Clôturer votre journée</h1>
                        <p class="text-purple-100">Vérifiez les chiffres et saisissez le montant compté en caisse</p>
                    </div>

                    <div class="p-6 space-y-6">

                        <!-- Résumé de la session -->
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3">Résumé de la session</h3>
                            <div class="bg-gray-50 rounded-xl p-5 space-y-3">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Ouverte à</span>
                                    <span class="font-medium text-gray-900">{{ formatHeure(session.ouverte_le) }}</span>
                                </div>
                                <div class="flex justify-between text-sm border-t border-gray-200 pt-3">
                                    <span class="text-gray-600">Fond initial</span>
                                    <span class="font-medium text-gray-900">{{ formatFCFA(session.fond_caisse_initial) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Tickets vendus</span>
                                    <span class="font-medium text-gray-900">{{ nombreTickets }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Total ventes</span>
                                    <span class="font-medium text-emerald-600">+ {{ formatFCFA(totalVentes) }}</span>
                                </div>
                                <div v-if="totalDepenses > 0" class="flex justify-between text-sm">
                                    <span class="text-gray-600">Total dépenses</span>
                                    <span class="font-medium text-red-600">− {{ formatFCFA(totalDepenses) }}</span>
                                </div>
                                <div class="flex justify-between border-t-2 border-purple-200 pt-3">
                                    <span class="font-bold text-gray-900">Montant attendu</span>
                                    <span class="text-xl font-bold text-purple-700">{{ formatFCFA(montantAttendu) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Saisie montant compté -->
                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Montant compté en caisse <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        v-model="form.montant_compte"
                                        type="number"
                                        step="50"
                                        min="0"
                                        required
                                        autofocus
                                        placeholder="0"
                                        class="w-full px-4 py-3 pr-16 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-purple-500 focus:ring-2 focus:ring-purple-200 outline-none transition text-2xl font-bold"
                                    />
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-500">FCFA</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Comptez les espèces en caisse et saisissez le total ici.</p>
                                <p v-if="form.errors.montant_compte" class="mt-1 text-sm text-red-600">{{ form.errors.montant_compte }}</p>
                            </div>

                            <!-- Aperçu écart -->
                            <div v-if="ecart !== null" class="rounded-xl p-4 border-2" :class="ecart === 0 ? 'bg-emerald-50 border-emerald-200' : (ecart > 0 ? 'bg-blue-50 border-blue-200' : 'bg-red-50 border-red-200')">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold" :class="ecart === 0 ? 'text-emerald-800' : (ecart > 0 ? 'text-blue-800' : 'text-red-800')">
                                        <span v-if="ecart === 0">Caisse équilibrée</span>
                                        <span v-else-if="ecart > 0">Excédent</span>
                                        <span v-else>Déficit</span>
                                    </span>
                                    <span class="text-xl font-bold" :class="ecart === 0 ? 'text-emerald-700' : (ecart > 0 ? 'text-blue-700' : 'text-red-700')">
                                        {{ ecart > 0 ? '+' : '' }}{{ formatFCFA(ecart) }}
                                    </span>
                                </div>
                                <p class="text-xs mt-2" :class="ecart === 0 ? 'text-emerald-700' : (ecart > 0 ? 'text-blue-700' : 'text-red-700')">
                                    <span v-if="ecart === 0">Parfait, le compte est juste.</span>
                                    <span v-else-if="ecart > 0">Plus d'argent en caisse que prévu. Vérifiez les ventes non enregistrées.</span>
                                    <span v-else>Moins d'argent en caisse que prévu. Vérifiez les retraits non comptabilisés.</span>
                                </p>
                            </div>

                            <div class="flex gap-3 pt-4 border-t border-gray-100">
                                <Link
                                    :href="route('dashboard')"
                                    class="flex-1 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 text-center rounded-lg font-medium transition"
                                >
                                    Retour
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing || form.montant_compte === ''"
                                    class="flex-1 px-6 py-3 bg-purple-600 hover:bg-purple-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-lg font-semibold shadow-lg shadow-purple-200 transition"
                                >
                                    <span v-if="!form.processing">Clôturer la caisse</span>
                                    <span v-else>Clôture en cours...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
