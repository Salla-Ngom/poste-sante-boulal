<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    ticketTypes: Array,
});

const formatFCFA = (montant) => {
    return new Intl.NumberFormat('fr-FR').format(montant || 0) + ' FCFA';
};

const typeATogglet = ref(null);

const ouvrirToggle = (type) => {
    typeATogglet.value = type;
};

const fermerToggle = () => {
    typeATogglet.value = null;
};

const confirmerToggle = () => {
    router.post(route('ticket-types.toggle', typeATogglet.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => fermerToggle(),
    });
};
</script>

<template>
    <Head title="Types de prestations" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Types de prestations
                </h2>
                <Link
                    :href="route('ticket-types.create')"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-semibold shadow-md shadow-emerald-200 transition flex items-center gap-2"
                >
                    <span class="text-lg leading-none">+</span>
                    Nouveau type
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <p class="text-sm text-blue-900">
                        <span class="font-semibold">À savoir :</span>
                        Vous pouvez créer, modifier et désactiver les prestations.
                        Un type désactivé n'apparaît plus dans la vente mais l'historique des tickets vendus reste intact.
                    </p>
                </div>

                <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Libellé</th>
                                    <th class="text-right px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Prix</th>
                                    <th class="text-center px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Statut</th>
                                    <th class="text-center px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Ventes</th>
                                    <th class="text-right px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="type in ticketTypes" :key="type.id" class="hover:bg-gray-50 transition" :class="{ 'opacity-60': !type.actif }">
                                    <td class="px-5 py-4">
                                        <span class="font-medium text-gray-900">{{ type.libelle }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <span class="font-semibold text-emerald-600">{{ formatFCFA(type.prix) }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span v-if="type.actif" class="inline-block px-3 py-1 bg-emerald-100 text-emerald-700 text-xs font-semibold rounded-full">
                                            Actif
                                        </span>
                                        <span v-else class="inline-block px-3 py-1 bg-gray-100 text-gray-600 text-xs font-semibold rounded-full">
                                            Désactivé
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span class="text-sm font-medium text-gray-700">
                                            {{ type.total_ventes }}
                                        </span>
                                        <span class="text-xs text-gray-400 ml-1">vendus</span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <Link
                                                :href="route('ticket-types.edit', type.id)"
                                                class="text-emerald-600 hover:text-emerald-700 text-sm font-medium"
                                            >
                                                Modifier
                                            </Link>
                                            <button
                                                @click="ouvrirToggle(type)"
                                                class="text-sm font-medium"
                                                :class="type.actif ? 'text-red-600 hover:text-red-700' : 'text-blue-600 hover:text-blue-700'"
                                            >
                                                {{ type.actif ? 'Désactiver' : 'Réactiver' }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="ticketTypes.length === 0">
                                    <td colspan="5" class="px-5 py-12 text-center">
                                        <p class="text-gray-500 font-medium mb-1">Aucun type de prestation</p>
                                        <p class="text-sm text-gray-400">Créez votre premier type avec le bouton en haut.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal de confirmation toggle -->
        <div v-if="typeATogglet" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                        {{ typeATogglet.actif ? 'Désactiver' : 'Réactiver' }} la prestation ?
                    </h3>
                    <p class="text-sm text-gray-600 mb-4">
                        <span class="font-semibold">{{ typeATogglet.libelle }}</span>
                        <span v-if="typeATogglet.actif">
                            ne sera plus disponible pour la vente, mais les tickets déjà vendus resteront dans l'historique.
                        </span>
                        <span v-else>
                            redeviendra disponible pour la vente.
                        </span>
                    </p>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex gap-3">
                    <button
                        @click="fermerToggle"
                        class="flex-1 px-4 py-2 bg-white hover:bg-gray-100 border border-gray-200 text-gray-700 rounded-lg font-medium transition text-sm"
                    >
                        Annuler
                    </button>
                    <button
                        @click="confirmerToggle"
                        class="flex-1 px-4 py-2 text-white rounded-lg font-semibold transition text-sm"
                        :class="typeATogglet.actif ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700'"
                    >
                        Confirmer
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
