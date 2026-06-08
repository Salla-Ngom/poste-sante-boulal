<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    ticketType: Object,
});

const isEdit = computed(() => !!props.ticketType);

const form = useForm({
    libelle: props.ticketType?.libelle || '',
    prix: props.ticketType?.prix || '',
    actif: props.ticketType?.actif ?? true,
});

const submit = () => {
    if (isEdit.value) {
        form.put(route('ticket-types.update', props.ticketType.id));
    } else {
        form.post(route('ticket-types.store'));
    }
};

const formatFCFA = (montant) => {
    return new Intl.NumberFormat('fr-FR').format(montant || 0) + ' FCFA';
};
</script>

<template>
    <Head :title="isEdit ? 'Modifier la prestation' : 'Nouvelle prestation'" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ isEdit ? 'Modifier la prestation' : 'Nouvelle prestation' }}
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

                    <div class="bg-gradient-to-r from-emerald-600 to-teal-600 p-6 text-white">
                        <h1 class="text-2xl font-bold mb-1">
                            {{ isEdit ? 'Modifier' : 'Créer' }} une prestation
                        </h1>
                        <p class="text-emerald-100">
                            {{ isEdit ? 'Modifiez les informations de cette prestation' : 'Définissez le libellé et le tarif de la prestation' }}
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="p-6 space-y-6">

                        <div>
                            <label for="libelle" class="block text-sm font-medium text-gray-700 mb-2">
                                Libellé <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="libelle"
                                v-model="form.libelle"
                                type="text"
                                required
                                autofocus
                                placeholder="Ex: Consultation adulte"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition"
                            />
                            <p class="text-xs text-gray-500 mt-1">Nom court et clair de la prestation tel qu'il apparaîtra dans la liste de vente.</p>
                            <p v-if="form.errors.libelle" class="mt-1 text-sm text-red-600">
                                {{ form.errors.libelle }}
                            </p>
                        </div>

                        <div>
                            <label for="prix" class="block text-sm font-medium text-gray-700 mb-2">
                                Prix officiel (FCFA) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    id="prix"
                                    v-model="form.prix"
                                    type="number"
                                    step="1"
                                    min="0"
                                    required
                                    placeholder="Ex: 1000"
                                    class="w-full px-4 py-3 pr-16 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition text-lg font-semibold"
                                />
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-500">FCFA</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Tarif officiel pré-rempli automatiquement lors de la vente. Le caissier peut le modifier au cas par cas.</p>
                            <p v-if="form.errors.prix" class="mt-1 text-sm text-red-600">
                                {{ form.errors.prix }}
                            </p>
                            <div v-if="form.prix > 0" class="mt-2 p-3 bg-emerald-50 border border-emerald-200 rounded-lg">
                                <p class="text-sm text-emerald-800">
                                    <span class="font-semibold">Aperçu :</span> {{ formatFCFA(form.prix) }}
                                </p>
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
                                    <span class="block font-medium text-gray-900">Prestation active</span>
                                    <span class="block text-sm text-gray-600 mt-0.5">
                                        Si décochée, cette prestation n'apparaîtra plus dans la liste de vente.
                                    </span>
                                </div>
                            </label>
                        </div>

                        <div v-if="isEdit && ticketType.total_ventes > 0" class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                            <p class="text-sm text-amber-900">
                                <span class="font-semibold">Note :</span>
                                Cette prestation a déjà été vendue {{ ticketType.total_ventes }} fois.
                                Le changement de prix n'affectera pas l'historique : les tickets passés conservent leur prix d'origine.
                            </p>
                        </div>

                        <div class="flex gap-3 pt-4 border-t border-gray-100">
                            <Link
                                :href="route('ticket-types.index')"
                                class="flex-1 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 text-center rounded-lg font-medium transition"
                            >
                                Annuler
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex-1 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-400 text-white rounded-lg font-semibold shadow-lg shadow-emerald-200 transition"
                            >
                                <span v-if="!form.processing">{{ isEdit ? 'Enregistrer' : 'Créer la prestation' }}</span>
                                <span v-else>Enregistrement...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
