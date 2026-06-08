<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    session: Object,
});

const formatFCFA = (montant) => {
    return new Intl.NumberFormat('fr-FR').format(montant || 0) + ' FCFA';
};

const formatDateTime = (dateString) => {
    if (!dateString) return '—';
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

const couleurEcart = (ecart) => {
    if (ecart == 0) return 'text-emerald-600';
    if (ecart > 0) return 'text-blue-600';
    return 'text-red-600';
};
</script>

<template>

    <Head title="Rapport de caisse - PS Boulal" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Rapport de clôture
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

                <!-- Bandeau succès -->
                <div class="bg-gradient-to-r from-emerald-600 to-teal-600 rounded-2xl p-6 text-white shadow-lg">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center text-3xl font-bold">
                            ✓
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold mb-1">Caisse clôturée avec succès</h1>
                            <p class="text-emerald-100">Le rapport PDF a été généré et archivé</p>
                        </div>
                    </div>
                </div>

                <!-- Récapitulatif -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-900">Récapitulatif de la session</h3>
                    </div>

                    <div class="p-5 space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 text-sm">Caissier</span>
                            <span class="font-medium text-gray-900">{{ session.user?.name || '—' }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 text-sm">Ouverte le</span>
                            <span class="font-medium text-gray-900">{{ formatDateTime(session.ouverte_le) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 text-sm">Fermée le</span>
                            <span class="font-medium text-gray-900">{{ formatDateTime(session.fermee_le) }}</span>
                        </div>
                        <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                            <span class="text-gray-600 text-sm">Fond initial</span>
                            <span class="font-medium text-gray-900">{{ formatFCFA(session.fond_caisse_initial) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 text-sm">Montant compté</span>
                            <span class="font-medium text-gray-900">{{ formatFCFA(session.montant_compte) }}</span>
                        </div>
                        <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                            <span class="font-semibold text-gray-900">Écart</span>
                            <span class="font-bold text-xl" :class="couleurEcart(session.ecart)">
                                {{ session.ecart > 0 ? '+' : '' }}{{ formatFCFA(session.ecart) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Téléchargement PDF -->
                <div class="bg-white rounded-2xl border-2 border-emerald-200 shadow-sm p-6">
                    <div class="flex items-start gap-4 mb-4">
                        <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center text-emerald-600 font-bold text-xl">
                            PDF
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900 mb-1">Rapport disponible</h3>
                            <p class="text-sm text-gray-600">
                                Document signé numériquement. Imprimez-le pour archivage.
                            </p>
                        </div>
                    </div>
                    <a :href="route('caisse.rapport.telecharger', session.id)"
                        class="block w-full px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-center rounded-lg font-semibold shadow-lg shadow-emerald-200 transition">
                        Télécharger le rapport PDF
                    </a>
                </div>

                <div class="flex gap-3">
                    <Link :href="route('dashboard')"
                        class="flex-1 px-6 py-3 bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 text-center rounded-lg font-medium transition">
                        Retour au tableau de bord
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
