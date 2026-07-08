<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
    stats: Array,
    totaux: Object,
    filtres: Object,
})

// ─── Filtres de période ──────────────────────────────────────────────────────
const debut = ref(props.filtres.debut)
const fin   = ref(props.filtres.fin)
const recherche = ref('')

const appliquer = () => {
    router.get(route('medicaments.stats'), {
        debut: debut.value,
        fin: fin.value,
    }, {
        preserveScroll: true,
    })
}

// Formatage local yyyy-mm-dd (sans décalage de fuseau)
const iso = (d) => {
    const yyyy = d.getFullYear()
    const mm = String(d.getMonth() + 1).padStart(2, '0')
    const dd = String(d.getDate()).padStart(2, '0')
    return `${yyyy}-${mm}-${dd}`
}

const presets = [
    {
        label: "Aujourd'hui",
        calc: () => { const t = new Date(); return [iso(t), iso(t)] },
    },
    {
        label: '7 derniers jours',
        calc: () => {
            const f = new Date()
            const d = new Date(); d.setDate(d.getDate() - 6)
            return [iso(d), iso(f)]
        },
    },
    {
        label: 'Ce mois',
        calc: () => {
            const t = new Date()
            return [iso(new Date(t.getFullYear(), t.getMonth(), 1)), iso(t)]
        },
    },
    {
        label: 'Mois dernier',
        calc: () => {
            const t = new Date()
            const d = new Date(t.getFullYear(), t.getMonth() - 1, 1)
            const f = new Date(t.getFullYear(), t.getMonth(), 0)
            return [iso(d), iso(f)]
        },
    },
]

const presetActif = (p) => {
    const [d, f] = p.calc()
    return debut.value === d && fin.value === f
}

const choisirPreset = (p) => {
    const [d, f] = p.calc()
    debut.value = d
    fin.value = f
    appliquer()
}

// ─── Recherche client (sans rechargement) ────────────────────────────────────
const statsFiltrees = computed(() => {
    const q = recherche.value.trim().toLowerCase()
    if (!q) return props.stats
    return props.stats.filter((s) =>
        s.libelle_medicament.toLowerCase().includes(q)
    )
})

// ─── Formatage ────────────────────────────────────────────────────────────────
const formatFCFA = (montant) =>
    new Intl.NumberFormat('fr-FR').format(montant || 0) + ' FCFA'

const formatNombre = (n) =>
    new Intl.NumberFormat('fr-FR').format(n || 0)

const formatDate = (dateString) =>
    new Date(dateString + 'T00:00:00').toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'long', year: 'numeric',
    })

// Badge stock : rupture / alerte / ok
const etatStock = (s) => {
    if (s.stock_actuel === null) return { label: 'Supprimé', classes: 'bg-gray-100 text-gray-500' }
    if (s.stock_actuel <= 0) return { label: 'Rupture', classes: 'bg-red-100 text-red-700' }
    if (s.stock_actuel <= s.seuil_alerte) return { label: `${s.stock_actuel} — Alerte`, classes: 'bg-amber-100 text-amber-700' }
    return { label: String(s.stock_actuel), classes: 'bg-gray-100 text-gray-700' }
}
</script>

<template>
    <Head title="Stats ventes médicaments" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Suivi des ventes de médicaments
            </h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-4">

                <!-- ===== Filtre de période ===== -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                    <div class="flex flex-wrap items-end gap-3">

                        <!-- Raccourcis -->
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="p in presets"
                                :key="p.label"
                                @click="choisirPreset(p)"
                                class="px-3 py-2 rounded-lg text-sm font-medium border transition"
                                :class="presetActif(p)
                                    ? 'bg-purple-600 text-white border-purple-600'
                                    : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100'"
                            >
                                {{ p.label }}
                            </button>
                        </div>

                        <div class="h-8 w-px bg-gray-200 hidden sm:block"></div>

                        <!-- Dates personnalisées -->
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Du</label>
                            <input v-model="debut" type="date"
                                class="rounded-lg border-gray-200 text-sm focus:border-purple-500 focus:ring-purple-200" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Au</label>
                            <input v-model="fin" type="date"
                                class="rounded-lg border-gray-200 text-sm focus:border-purple-500 focus:ring-purple-200" />
                        </div>
                        <button
                            @click="appliquer"
                            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-semibold transition"
                        >
                            Appliquer
                        </button>
                    </div>

                    <p class="text-xs text-gray-500 mt-3">
                        Période : du {{ formatDate(filtres.debut) }} au {{ formatDate(filtres.fin) }}
                    </p>
                </div>

                <!-- ===== KPIs ===== -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                        <p class="text-xs text-gray-500 mb-1">Unités vendues</p>
                        <p class="text-2xl font-bold text-purple-600">{{ formatNombre(totaux.unitesVendues) }}</p>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                        <p class="text-xs text-gray-500 mb-1">Chiffre d'affaires</p>
                        <p class="text-2xl font-bold text-emerald-600">{{ formatFCFA(totaux.chiffreAffaires) }}</p>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                        <p class="text-xs text-gray-500 mb-1">Médicaments distincts</p>
                        <p class="text-2xl font-bold text-gray-800">{{ formatNombre(totaux.medicamentsDistincts) }}</p>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                        <p class="text-xs text-gray-500 mb-1">Tickets pharmacie</p>
                        <p class="text-2xl font-bold text-gray-800">{{ formatNombre(totaux.nombreTickets) }}</p>
                    </div>
                </div>

                <!-- ===== Tableau ===== -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

                    <!-- Recherche -->
                    <div class="p-4 border-b border-gray-100">
                        <input
                            v-model="recherche"
                            type="search"
                            placeholder="Rechercher un médicament..."
                            class="w-full sm:w-72 rounded-lg border-gray-200 text-sm focus:border-purple-500 focus:ring-purple-200"
                        />
                    </div>

                    <!-- État vide -->
                    <div v-if="statsFiltrees.length === 0" class="p-12 text-center text-gray-500">
                        <p class="text-lg font-medium mb-1">Aucune vente sur cette période</p>
                        <p class="text-sm">Modifiez la période ou la recherche pour voir des résultats.</p>
                    </div>

                    <!-- Table -->
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 text-left text-xs text-gray-500 uppercase tracking-wide">
                                    <th class="px-4 py-3 w-10">#</th>
                                    <th class="px-4 py-3">Médicament</th>
                                    <th class="px-4 py-3 text-right">Quantité vendue</th>
                                    <th class="px-4 py-3 text-right">Nb ventes</th>
                                    <th class="px-4 py-3 text-right">Chiffre d'affaires</th>
                                    <th class="px-4 py-3 text-right">Stock actuel</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="(s, i) in statsFiltrees"
                                    :key="s.medicament_id + s.libelle_medicament"
                                    class="hover:bg-gray-50 transition"
                                >
                                    <td class="px-4 py-3 text-gray-400">{{ i + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-gray-900">{{ s.libelle_medicament }}</p>
                                        <p v-if="s.forme" class="text-xs text-gray-500">{{ s.forme }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="font-bold text-purple-600 text-base">{{ formatNombre(s.quantite_vendue) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ formatNombre(s.nombre_ventes) }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-emerald-600">{{ formatFCFA(s.chiffre_affaires) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <span
                                            class="inline-block px-2 py-1 rounded-full text-xs font-semibold"
                                            :class="etatStock(s).classes"
                                        >
                                            {{ etatStock(s).label }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
