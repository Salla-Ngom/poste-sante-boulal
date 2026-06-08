<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    session: Object,
    depenses: Array,
    totalDepenses: Number,
});

const form = useForm({
    libelle: '',
    montant: '',
});

const submit = () => {
    form.post(route('depenses.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const formatFCFA = (montant) => {
    return new Intl.NumberFormat('fr-FR').format(montant || 0) + ' FCFA';
};

const formatDateTime = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

// Alerte si montant élevé (> 50 000 FCFA)
const montantEleve = computed(() => {
    return form.montant && Number(form.montant) > 50000;
});
</script>

<template>
    <Head title="Dépenses" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dépenses de la session
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

                <!-- ===== Bandeau session ===== -->
                <div class="bg-gradient-to-r from-emerald-600 to-teal-600 rounded-xl p-5 text-white shadow-lg">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <p class="text-emerald-100 text-sm">Session active</p>
                            <p class="font-semibold text-lg">{{ session.user?.name || 'Caissier' }}</p>
                            <p class="text-sm text-emerald-100">Fond initial : {{ formatFCFA(session.fond_caisse_initial) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-emerald-100 text-sm">Total dépenses</p>
                            <p class="text-3xl font-bold">{{ formatFCFA(totalDepenses) }}</p>
                        </div>
                    </div>
                </div>

                <!-- ===== Formulaire d'ajout ===== -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

                    <div class="p-5 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-900">Enregistrer une dépense</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Toute sortie d'argent sur la caisse pendant la session.
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="p-5 space-y-4">

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="sm:col-span-2">
                                <label for="libelle" class="block text-sm font-medium text-gray-700 mb-2">
                                    Libellé <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="libelle"
                                    v-model="form.libelle"
                                    type="text"
                                    required
                                    placeholder="Ex: Achat de gants chirurgicaux"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition"
                                />
                                <p v-if="form.errors.libelle" class="mt-1 text-sm text-red-600">{{ form.errors.libelle }}</p>
                            </div>

                            <div>
                                <label for="montant" class="block text-sm font-medium text-gray-700 mb-2">
                                    Montant <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        id="montant"
                                        v-model="form.montant"
                                        type="number"
                                        step="1"
                                        min="1"
                                        required
                                        placeholder="0"
                                        class="w-full px-4 py-2.5 pr-16 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition font-semibold"
                                    />
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-500">FCFA</span>
                                </div>
                                <p v-if="form.errors.montant" class="mt-1 text-sm text-red-600">{{ form.errors.montant }}</p>
                            </div>
                        </div>

                        <div v-if="montantEleve" class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                            <p class="text-sm text-amber-900">
                                <span class="font-semibold">Attention :</span> Le montant saisi ({{ formatFCFA(form.montant) }}) est élevé. Vérifiez la saisie avant de valider.
                            </p>
                        </div>

                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                            <p class="text-xs text-amber-900">
                                <span class="font-semibold">Note :</span> Une dépense enregistrée est définitive et ne peut pas être supprimée. Vérifiez bien le libellé et le montant avant de valider.
                            </p>
                        </div>

                        <div class="flex gap-3">
                            <Link
                                :href="route('dashboard')"
                                class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition text-sm"
                            >
                                Retour
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex-1 px-6 py-2.5 bg-red-600 hover:bg-red-700 disabled:bg-red-400 text-white rounded-lg font-semibold shadow-md shadow-red-200 transition text-sm"
                            >
                                <span v-if="!form.processing">Enregistrer la dépense</span>
                                <span v-else>Enregistrement...</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ===== Liste des dépenses ===== -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

                    <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-900">Dépenses enregistrées</h3>
                            <p class="text-sm text-gray-500 mt-1">{{ depenses.length }} dépense{{ depenses.length > 1 ? 's' : '' }} sur cette session</p>
                        </div>
                    </div>

                    <div v-if="depenses.length === 0" class="p-12 text-center">
                        <p class="text-gray-500 font-medium mb-1">Aucune dépense enregistrée</p>
                        <p class="text-sm text-gray-400">Utilisez le formulaire ci-dessus pour ajouter une dépense.</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Date / Heure</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Libellé</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Auteur</th>
                                    <th class="text-right px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Montant</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="depense in depenses" :key="depense.id" class="hover:bg-gray-50 transition">
                                    <td class="px-5 py-3 text-sm text-gray-600">
                                        {{ formatDateTime(depense.depense_le) }}
                                    </td>
                                    <td class="px-5 py-3 text-sm text-gray-900 font-medium">
                                        {{ depense.libelle }}
                                    </td>
                                    <td class="px-5 py-3 text-sm text-gray-600">
                                        {{ depense.user.name }}
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <span class="font-semibold text-red-600">- {{ formatFCFA(depense.montant) }}</span>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                                <tr>
                                    <td colspan="3" class="px-5 py-3 text-sm font-semibold text-gray-900 text-right">
                                        TOTAL
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <span class="font-bold text-red-600 text-lg">- {{ formatFCFA(totalDepenses) }}</span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
