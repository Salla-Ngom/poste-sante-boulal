<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    session: Object,
    ticketTypes: Array,
});

const form = useForm({
    ticket_type_id: '',
    prix_paye: '',
});

// Type sélectionné (objet complet)
const selectedType = computed(() => {
    return props.ticketTypes.find(t => t.id === form.ticket_type_id);
});

// Quand le type change, on pré-remplit automatiquement le prix
watch(() => form.ticket_type_id, (newId) => {
    if (newId) {
        const type = props.ticketTypes.find(t => t.id === newId);
        if (type) {
            form.prix_paye = type.prix;
        }
    }
});

const formatFCFA = (montant) => {
    return new Intl.NumberFormat('fr-FR').format(montant || 0) + ' FCFA';
};

const ecartPrix = computed(() => {
    if (!selectedType.value || form.prix_paye === '') return null;
    return Number(form.prix_paye) - Number(selectedType.value.prix);
});

const submit = () => {
    form.post(route('tickets.store'));
};
</script>

<template>
    <Head title="Vendre un ticket" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Vendre un ticket
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

                    <div class="bg-gradient-to-r from-emerald-600 to-teal-600 p-6 text-white">
                        <h1 class="text-2xl font-bold mb-1">Nouveau ticket</h1>
                        <p class="text-emerald-100">Sélectionnez la prestation et validez le montant</p>
                    </div>

                    <form @submit.prevent="submit" class="p-6 space-y-6">

                        <!-- ===== Bloc Type de prestation ===== -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                Type de prestation <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label
                                    v-for="type in ticketTypes"
                                    :key="type.id"
                                    class="cursor-pointer"
                                >
                                    <input
                                        type="radio"
                                        :value="type.id"
                                        v-model="form.ticket_type_id"
                                        class="sr-only peer"
                                    />
                                    <div class="p-4 bg-white border-2 border-gray-200 rounded-xl peer-checked:border-emerald-500 peer-checked:bg-emerald-50 transition hover:border-emerald-300">
                                        <p class="font-semibold text-gray-900 mb-1">{{ type.libelle }}</p>
                                        <p class="text-sm text-emerald-600 font-medium">{{ formatFCFA(type.prix) }}</p>
                                    </div>
                                </label>
                            </div>
                            <p v-if="form.errors.ticket_type_id" class="mt-2 text-sm text-red-600">
                                {{ form.errors.ticket_type_id }}
                            </p>
                        </div>

                        <!-- ===== Bloc Montant payé ===== -->
                        <div v-if="selectedType">
                            <label for="prix" class="block text-sm font-medium text-gray-700 mb-2">
                                Montant payé (FCFA) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    id="prix"
                                    v-model="form.prix_paye"
                                    type="number"
                                    step="1"
                                    min="0"
                                    required
                                    class="w-full px-4 py-3 pr-16 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition text-lg font-semibold"
                                />
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-500">FCFA</span>
                            </div>
                            <p v-if="form.errors.prix_paye" class="mt-2 text-sm text-red-600">
                                {{ form.errors.prix_paye }}
                            </p>

                            <div v-if="ecartPrix !== null && ecartPrix !== 0" class="mt-3 p-3 rounded-lg" :class="ecartPrix > 0 ? 'bg-blue-50 border border-blue-200' : 'bg-amber-50 border border-amber-200'">
                                <p class="text-sm" :class="ecartPrix > 0 ? 'text-blue-800' : 'text-amber-800'">
                                    <span class="font-semibold">Note :</span>
                                    <span v-if="ecartPrix > 0">
                                        Le montant payé est supérieur au tarif officiel ({{ formatFCFA(selectedType.prix) }}). Différence : +{{ formatFCFA(ecartPrix) }}.
                                    </span>
                                    <span v-else>
                                        Le montant payé est inférieur au tarif officiel ({{ formatFCFA(selectedType.prix) }}). Remise : {{ formatFCFA(ecartPrix) }}.
                                    </span>
                                </p>
                            </div>
                        </div>

                        <!-- ===== Boutons d'action ===== -->
                        <div class="flex gap-3 pt-4 border-t border-gray-100">
                            <Link
                                :href="route('dashboard')"
                                class="flex-1 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 text-center rounded-lg font-medium transition"
                            >
                                Annuler
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing || !form.ticket_type_id"
                                class="flex-1 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-lg font-semibold shadow-lg shadow-emerald-200 transition"
                            >
                                <span v-if="!form.processing">Valider la vente</span>
                                <span v-else>Enregistrement...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
