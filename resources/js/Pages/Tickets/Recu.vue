<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { onMounted, ref } from 'vue'
import axios from 'axios'

const props = defineProps({
    ticket: Object,
})

const impressionBTEnCours = ref(false)
const impressionBTOk      = ref(false)
const impressionErreur   = ref(null)
const modePWA             = ref(false)
const bluetoothDisponible = ref(false)

// ─── Détection PWA ────────────────────────────────────────────────────────────
const estPWA = () =>
    window.matchMedia('(display-mode: standalone)').matches ||
    window.navigator.standalone === true

// ─── Formatage ────────────────────────────────────────────────────────────────
const formatFCFA = (montant) =>
    new Intl.NumberFormat('fr-FR').format(montant || 0) + ' FCFA'

const formatDate = (dateString) =>
    new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
    })

const formatHeure = (dateString) =>
    new Date(dateString).toLocaleTimeString('fr-FR', {
        hour: '2-digit', minute: '2-digit',
    })

const numeroFormate = (date, numero) => {
    const d = new Date(date)
    const yyyy = d.getFullYear()
    const mm   = String(d.getMonth() + 1).padStart(2, '0')
    const dd   = String(d.getDate()).padStart(2, '0')
    const num  = String(numero).padStart(4, '0')
    return `${yyyy}${mm}${dd}-${num}`
}

// ─── Impression via Flutter Bridge ──────────────────────────────────────────
const imprimerBluetooth = async () => {
    impressionBTEnCours.value = true
    impressionBTOk.value      = false
    impressionErreur.value    = null

    try {
        const response = await axios.post(
            `/api/tickets/${props.ticket.id}/print-bluetooth`
        )

        if (response.data.success) {
            impressionBTOk.value = true
        } else {
            impressionErreur.value = response.data.message || 'Erreur d\'impression'
        }
    } catch (error) {
        impressionErreur.value = error.response?.data?.message ||
                                 'Impossible de contacter le service d\'impression'
        console.error('Erreur impression:', error)
    } finally {
        impressionBTEnCours.value = false
    }
}

// ─── Au montage ──────────────────────────────────────────────────────────────
onMounted(() => {
    modePWA.value = estPWA()
    bluetoothDisponible.value = typeof navigator !== 'undefined' &&
                                typeof navigator.bluetooth !== 'undefined'

    if (!modePWA.value) {
        setTimeout(() => window.print(), 500)
    }
})

const reimprimer = () => {
    if (modePWA.value) {
        imprimerBluetooth()
    } else {
        window.print()
    }
}
</script>

<template>
    <Head title="Reçu - PS Boulal" />

    <div class="min-h-screen bg-gray-100 py-8 print:bg-white print:py-0">

        <!-- ===== Barre d'actions (cachée à l'impression) ===== -->
        <div class="max-w-md mx-auto px-4 mb-4 print:hidden">

            <!-- Confirmation vente -->
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex items-start gap-3 mb-4">
                <div class="text-emerald-600 text-2xl font-bold">✓</div>
                <div class="flex-1">
                    <h3 class="font-semibold text-emerald-900">Ticket vendu avec succès</h3>
                    <p class="text-sm text-emerald-700">
                        Numéro : {{ numeroFormate(ticket.date_emission, ticket.numero) }}
                    </p>
                </div>
            </div>

            <!-- ── Mode PWA : bouton Bluetooth ── -->
            <div v-if="modePWA" class="space-y-3 mb-4">

                <!-- Statut -->
                <div v-if="bluetoothDisponible" class="flex items-center gap-2 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                    Prêt pour l'impression Bluetooth
                </div>
                <div v-else class="flex items-center gap-2 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                    ⚠ Bluetooth non disponible — utilisez Chrome Android
                </div>

                <!-- Erreur -->
                <div v-if="impressionErreur" class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                    ⚠ {{ impressionErreur }}
                </div>

                <!-- Confirmation OK -->
                <div v-if="impressionBTOk" class="flex items-center gap-2 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2">
                    ✓ Ticket imprimé avec succès
                </div>

                <!-- Bouton principal -->
                <button
                    @click="imprimerBluetooth"
                    :disabled="impressionBTEnCours"
                    class="w-full px-4 py-4 rounded-xl font-semibold text-white transition text-base"
                    :class="impressionBTEnCours
                        ? 'bg-gray-400 cursor-not-allowed'
                        : 'bg-blue-600 active:bg-blue-800'"
                >
                    <span v-if="impressionBTEnCours">Impression en cours...</span>
                    <span v-else>🖨 Imprimer le ticket</span>
                </button>
            </div>

            <!-- ── Boutons navigation ── -->
            <div class="flex gap-2">
                <Link
                    :href="route('tickets.vendre')"
                    class="flex-1 px-4 py-3 bg-emerald-600 active:bg-emerald-800 text-white text-center rounded-lg font-semibold transition"
                >
                    Nouveau ticket
                </Link>
                <button
                    v-if="!modePWA"
                    @click="reimprimer"
                    class="px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition"
                >
                    Réimprimer
                </button>
                <Link
                    :href="route('dashboard')"
                    class="px-4 py-3 bg-gray-200 active:bg-gray-300 text-gray-700 rounded-lg font-medium transition"
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
                    <h1 class="text-xl font-bold text-gray-900 mb-1 leading-tight">POSTE DE SANTÉ DE BOULAL</h1>
                    <p class="text-xs text-gray-500 mb-2">PS Boulal</p>
                    <p class="text-sm text-gray-700 font-semibold tracking-wider">REÇU DE PAIEMENT</p>
                </div>

                <!-- Numéro -->
                <div class="text-center py-6">
                    <p class="text-xs text-gray-500 mb-1">Numéro de ticket</p>
                    <p class="text-3xl font-bold text-gray-900 tracking-wider">
                        N° {{ String(ticket.numero).padStart(4, '0') }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">{{ numeroFormate(ticket.date_emission, ticket.numero) }}</p>
                </div>

                <!-- Détails -->
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
                        <span class="text-gray-600">Caissier</span>
                        <span class="font-medium text-gray-900">{{ ticket.user.name }}</span>
                    </div>
                </div>

                <!-- Prestation -->
                <div class="py-4 border-t-2 border-dashed border-gray-300">
                    <p class="text-xs text-gray-500 mb-2">PRESTATION</p>
                    <p class="text-lg font-semibold text-gray-900 mb-3">{{ ticket.ticket_type.libelle }}</p>
                    <div class="bg-gray-50 rounded-lg p-3 flex justify-between items-center">
                        <span class="text-sm text-gray-600">Montant payé</span>
                        <span class="text-2xl font-bold text-emerald-600">
                            {{ formatFCFA(ticket.prix_paye) }}
                        </span>
                    </div>
                </div>

                <!-- Pied -->
                <div class="text-center pt-4 border-t-2 border-dashed border-gray-300">
                    <p class="text-xs text-gray-700 font-semibold mb-1">Merci - Bonne santé</p>
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
