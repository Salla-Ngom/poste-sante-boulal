<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';

const props = defineProps({
    ticket: Object,
});

const formatFCFA = (montant) => {
    return new Intl.NumberFormat('fr-FR').format(montant || 0) + ' FCFA';
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

const formatHeure = (dateString) => {
    return new Date(dateString).toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const numeroFormate = (date, numero) => {
    const d = new Date(date);
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    const num = String(numero).padStart(4, '0');
    return `${yyyy}${mm}${dd}-${num}`;
};

onMounted(() => {
    setTimeout(() => {
        window.print();
    }, 500);
});

const reimprimer = () => {
    window.print();
};
</script>

<template>
    <Head title="Reçu pharmacie" />

    <div class="min-h-screen bg-gray-100 py-8 print:bg-white print:py-0">

        <!-- ===== Barre d'actions (cachée à l'impression) ===== -->
        <div class="max-w-md mx-auto px-4 mb-4 print:hidden">
            <div class="bg-purple-50 border border-purple-200 rounded-xl p-4 flex items-start gap-3 mb-4">
                <div class="text-purple-600 text-2xl font-bold">P</div>
                <div class="flex-1">
                    <h3 class="font-semibold text-purple-900">Vente enregistrée avec succès</h3>
                    <p class="text-sm text-purple-700">
                        N° {{ numeroFormate(ticket.date_emission, ticket.numero) }}
                    </p>
                </div>
            </div>

            <div class="flex gap-2">
                <Link
                    :href="route('pharmacy.vendre')"
                    class="flex-1 px-4 py-3 bg-purple-600 hover:bg-purple-700 text-white text-center rounded-lg font-semibold transition"
                >
                    Nouvelle vente
                </Link>
                <button
                    @click="reimprimer"
                    class="px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition"
                >
                    Réimprimer
                </button>
                <Link
                    :href="route('dashboard')"
                    class="px-4 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium transition"
                >
                    Retour
                </Link>
            </div>
        </div>

        <!-- ===== Le reçu (zone imprimable) ===== -->
        <div class="max-w-md mx-auto bg-white shadow-lg print:shadow-none print:max-w-none">
            <div class="p-8 print:p-4">

                <!-- En-tête -->
                <div class="text-center pb-4 border-b-2 border-dashed border-gray-300">
                    <h1 class="text-2xl font-bold text-gray-900 mb-1">{{ ticket.tenant.name }}</h1>
                    <p class="text-sm text-gray-600 font-semibold">REÇU PHARMACIE</p>
                </div>

                <!-- Numéro -->
                <div class="text-center py-6">
                    <p class="text-xs text-gray-500 mb-1">N° de ticket pharmacie</p>
                    <p class="text-3xl font-bold text-purple-700 tracking-wider">
                        N° {{ String(ticket.numero).padStart(4, '0') }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">{{ numeroFormate(ticket.date_emission, ticket.numero) }}</p>
                </div>

                <!-- Détails session -->
                <div class="space-y-2 py-4 border-t-2 border-dashed border-gray-300">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Date</span>
                        <span class="font-medium text-gray-900">{{ formatDate(ticket.date_emission) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Heure</span>
                        <span class="font-medium text-gray-900">{{ formatHeure(ticket.emis_le) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Pharmacien</span>
                        <span class="font-medium text-gray-900">{{ ticket.user.name }}</span>
                    </div>
                </div>

                <!-- Patient -->
                <div class="py-4 border-t-2 border-dashed border-gray-300">
                    <p class="text-xs text-gray-500 mb-2">PATIENT</p>
                    <p class="text-base font-semibold text-gray-900">
                        {{ ticket.patient_prenom }} {{ ticket.patient_nom }}
                    </p>
                </div>

                <!-- Médicaments -->
                <div class="py-4 border-t-2 border-dashed border-gray-300">
                    <p class="text-xs text-gray-500 mb-3">MÉDICAMENTS</p>

                    <div class="space-y-3">
                        <div v-for="ligne in ticket.lines" :key="ligne.id" class="text-sm">
                            <p class="font-medium text-gray-900 mb-1">{{ ligne.libelle_medicament }}</p>
                            <div class="flex justify-between text-gray-700 pl-2">
                                <span>{{ formatFCFA(ligne.prix_unitaire) }} × {{ ligne.quantite }}</span>
                                <span class="font-medium">{{ formatFCFA(ligne.sous_total) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total -->
                <div class="py-4 border-t-2 border-dashed border-gray-300">
                    <div class="bg-purple-50 rounded-lg p-3 flex justify-between items-center">
                        <span class="text-sm font-semibold text-gray-700">TOTAL</span>
                        <span class="text-2xl font-bold text-purple-700">
                            {{ formatFCFA(ticket.total) }}
                        </span>
                    </div>

                    <!-- Prise en charge CMU -->
                    <div v-if="ticket.est_cmu" class="mt-2 bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-bold text-blue-800">PRISE EN CHARGE CMU</span>
                            <span class="text-lg font-bold text-blue-700">À payer : 0 FCFA</span>
                        </div>
                    </div>
                </div>

                <!-- Pied -->
                <div class="text-center pt-4 border-t-2 border-dashed border-gray-300">
                    <p class="text-xs text-gray-500 mb-1">Merci de votre confiance</p>
                    <p class="text-xs text-gray-400">Conservez ce reçu</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
@media print {
    @page {
        margin: 0.5cm;
        size: 80mm auto;
    }
    body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>
