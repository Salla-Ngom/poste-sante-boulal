<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

const props = defineProps({
    session: Object,
    ticketTypes: Array,
})

const form = useForm({
    ticket_type_id: '',
    prix_paye: '',
})

// Détection mobile / PWA
const estMobile = ref(
    /Android|iPhone|iPad/i.test(navigator.userAgent) ||
    window.matchMedia('(display-mode: standalone)').matches
)

// Type sélectionné
const selectedType = computed(() =>
    props.ticketTypes.find(t => t.id === form.ticket_type_id)
)

// Pré-remplir le prix quand le type change
watch(() => form.ticket_type_id, (newId) => {
    if (newId) {
        const type = props.ticketTypes.find(t => t.id === newId)
        if (type) form.prix_paye = type.prix
    }
})

const formatFCFA = (montant) =>
    new Intl.NumberFormat('fr-FR').format(montant || 0) + ' FCFA'

const ecartPrix = computed(() => {
    if (!selectedType.value || form.prix_paye === '') return null
    return Number(form.prix_paye) - Number(selectedType.value.prix)
})

// Clavier numérique tactile
const saisieRapide = (valeur) => {
    form.prix_paye = valeur
}

const submit = () => {
    form.post(route('tickets.store'))
}
</script>

<template>
    <Head title="Vendre un ticket" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Vendre un ticket
            </h2>
        </template>

        <div class="py-4 sm:py-8">
            <div class="max-w-3xl mx-auto px-3 sm:px-6 lg:px-8">

                <form @submit.prevent="submit" class="space-y-4">

                    <!-- ===== Info session ===== -->
                    <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-emerald-600 font-medium">Caisse ouverte</p>
                            <p class="text-sm text-emerald-900 font-semibold">
                                Fond : {{ new Intl.NumberFormat('fr-FR').format(session.fond_caisse_initial) }} FCFA
                            </p>
                        </div>
                        <Link :href="route('dashboard')" class="text-sm text-emerald-700 underline">
                            Dashboard
                        </Link>
                    </div>

                    <!-- ===== Sélection type de prestation ===== -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-4 pt-4 pb-2">
                            <p class="text-sm font-semibold text-gray-700">Prestation <span class="text-red-500">*</span></p>
                        </div>

                        <!-- Grille tactile : grandes cartes sur mobile -->
                        <div class="grid grid-cols-2 gap-2 p-3">
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
                                <div
                                    class="p-4 rounded-xl border-2 border-gray-200 bg-gray-50 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 transition active:scale-95"
                                    :class="estMobile ? 'min-h-[80px] flex flex-col justify-center' : ''"
                                >
                                    <p class="font-semibold text-gray-900 text-sm leading-snug mb-1">{{ type.libelle }}</p>
                                    <p class="text-emerald-600 font-bold" :class="estMobile ? 'text-base' : 'text-sm'">
                                        {{ formatFCFA(type.prix) }}
                                    </p>
                                </div>
                            </label>
                        </div>

                        <p v-if="form.errors.ticket_type_id" class="px-4 pb-3 text-sm text-red-600">
                            {{ form.errors.ticket_type_id }}
                        </p>
                    </div>

                    <!-- ===== Montant payé ===== -->
                    <div v-if="selectedType" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 space-y-3">
                        <p class="text-sm font-semibold text-gray-700">
                            Montant payé (FCFA) <span class="text-red-500">*</span>
                        </p>

                        <!-- Input numérique grand format pour les doigts -->
                        <div class="relative">
                            <input
                                v-model="form.prix_paye"
                                type="number"
                                inputmode="numeric"
                                step="1"
                                min="0"
                                required
                                class="w-full px-4 py-4 pr-16 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition font-bold"
                                :class="estMobile ? 'text-2xl' : 'text-lg'"
                            />
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-500">FCFA</span>
                        </div>

                        <!-- Raccourcis montants (utile sur mobile) -->
                        <div v-if="estMobile" class="grid grid-cols-3 gap-2">
                            <button
                                v-for="val in [500, 1000, 1500, 2000, 2500, 3000]"
                                :key="val"
                                type="button"
                                @click="saisieRapide(val)"
                                class="py-2 rounded-lg text-sm font-semibold border transition active:scale-95"
                                :class="form.prix_paye == val
                                    ? 'bg-emerald-600 text-white border-emerald-600'
                                    : 'bg-gray-50 text-gray-700 border-gray-200'"
                            >
                                {{ new Intl.NumberFormat('fr-FR').format(val) }}
                            </button>
                        </div>

                        <!-- Note écart prix -->
                        <div
                            v-if="ecartPrix !== null && ecartPrix !== 0"
                            class="p-3 rounded-lg text-sm"
                            :class="ecartPrix > 0 ? 'bg-blue-50 border border-blue-200 text-blue-800' : 'bg-amber-50 border border-amber-200 text-amber-800'"
                        >
                            <span class="font-semibold">Note :</span>
                            <span v-if="ecartPrix > 0">
                                Supérieur au tarif ({{ formatFCFA(selectedType.prix) }}). +{{ formatFCFA(ecartPrix) }}
                            </span>
                            <span v-else>
                                Inférieur au tarif ({{ formatFCFA(selectedType.prix) }}). {{ formatFCFA(ecartPrix) }}
                            </span>
                        </div>

                        <p v-if="form.errors.prix_paye" class="text-sm text-red-600">
                            {{ form.errors.prix_paye }}
                        </p>
                    </div>

                    <!-- ===== Boutons d'action ===== -->
                    <div class="flex gap-3">
                        <Link
                            :href="route('dashboard')"
                            class="flex-none px-5 py-4 bg-gray-100 active:bg-gray-200 text-gray-700 text-center rounded-xl font-medium transition"
                        >
                            Annuler
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing || !form.ticket_type_id"
                            class="flex-1 py-4 bg-emerald-600 active:bg-emerald-800 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-xl font-bold text-lg shadow-lg shadow-emerald-200 transition"
                        >
                            <span v-if="!form.processing">Valider la vente</span>
                            <span v-else>Enregistrement...</span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
