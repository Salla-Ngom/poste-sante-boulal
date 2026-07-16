<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { onMounted, ref } from 'vue'

const props = defineProps({
    ticket: Object,
})

const impressionEnCours = ref(false)
const impressionOk      = ref(false)
const impressionErreur  = ref(null)
const modeFlutter       = ref(false)

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

// ─── Impression via le pont Flutter (window.AndroidPrinter) ─────────────────
// L'app Flutter WebView injecte window.AndroidPrinter dans la page.
// AndroidPrinter.print(json) retourne une Promise {success, message}.
const imprimerFlutter = async () => {
    impressionEnCours.value = true
    impressionOk.value      = false
    impressionErreur.value  = null

    try {
        const result = await window.AndroidPrinter.print(
            JSON.stringify(props.ticket)
        )
        if (result && result.success) {
            impressionOk.value = true
        } else {
            impressionErreur.value =
                (result && result.message) || "Échec de l'impression"
        }
    } catch (e) {
        impressionErreur.value = 'Erreur : ' + (e.message || e)
    } finally {
        impressionEnCours.value = false
    }
}

// ─── Point d'entrée : Flutter → Bluetooth, sinon → window.print() ───────────
const imprimer = () => {
    if (window.AndroidPrinter) {
        modeFlutter.value = true
        imprimerFlutter()
    } else {
        window.print()
    }
}

// ─── Au montage : impression automatique ────────────────────────────────────
// Gestion de la course au chargement : le pont Flutter peut arriver
// quelques ms après le montage de Vue. On attend brièvement avant
// de basculer sur window.print() (mode PC).
onMounted(() => {
    setTimeout(() => {
        if (window.AndroidPrinter) {
            modeFlutter.value = true
            imprimerFlutter()
            return
        }

        let lance = false
        const onReady = () => {
            lance = true
            modeFlutter.value = true
            imprimerFlutter()
        }
        window.addEventListener('androidprinter-ready', onReady, { once: true })

        setTimeout(() => {
            if (!lance && !window.AndroidPrinter) {
                window.removeEventListener('androidprinter-ready', onReady)
                window.print()
            }
        }, 800)
    }, 300)
})

const reimprimer = () => imprimer()
</script>

<template>
    <Head title="Reçu - PS Boulal" />

    <div class="min-h-screen print:min-h-0 bg-gray-100 py-8 print:bg-white print:py-0">

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

            <!-- ── Statut impression Bluetooth (app Flutter) ── -->
            <div v-if="modeFlutter" class="space-y-3 mb-4">

                <div v-if="impressionEnCours" class="flex items-center gap-3 text-sm text-blue-800 bg-blue-50 border border-blue-200 rounded-lg px-4 py-3">
                    <svg class="animate-spin h-5 w-5 text-blue-600" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Impression Bluetooth en cours...
                </div>

                <div v-if="impressionOk" class="flex items-center gap-2 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg px-4 py-3">
                    ✓ Ticket imprimé avec succès
                </div>

                <div v-if="impressionErreur" class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-4 py-3">
                    {{ impressionErreur }}
                </div>
            </div>

            <!-- ── Boutons navigation ── -->
            <div class="flex gap-2">
                <Link
                    :href="route('tickets.vendre')"
                    class="flex-1 px-4 py-3 bg-emerald-600 active:bg-emerald-800 hover:bg-emerald-700 text-white text-center rounded-lg font-semibold transition"
                >
                    Nouveau ticket
                </Link>
                <button
                    @click="reimprimer"
                    :disabled="impressionEnCours"
                    class="px-4 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white rounded-lg font-semibold transition"
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
                    <h1 class="text-xl font-bold text-gray-900 mb-1 leading-tight">POSTE DE SANTÉ DE BOULAL</h1>
                    <p class="text-xs text-gray-500 mb-2">PS Boulal</p>
                    <p class="text-sm text-gray-700 font-semibold tracking-wider">REÇU DE PAIEMENT</p>
                </div>

                <!-- Numéro de ticket -->
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

                <!-- Patient (uniquement si renseigné) -->
                <div v-if="ticket.patient_nom" class="py-4 border-t-2 border-dashed border-gray-300">
                    <p class="text-xs text-gray-500 mb-2">PATIENT</p>
                    <p class="text-base font-semibold text-gray-900">
                        {{ ticket.patient_prenom }} {{ ticket.patient_nom }}
                    </p>
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
        margin: 0;
        size: 80mm auto;
    }
    html, body {
        height: auto !important;
    }
    body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>
