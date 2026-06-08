<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Line, Doughnut, Bar } from 'vue-chartjs';
import { computed } from 'vue';
import {
    Chart as ChartJS,
    Title, Tooltip, Legend,
    LineElement, PointElement,
    CategoryScale, LinearScale,
    ArcElement, BarElement,
} from 'chart.js';

ChartJS.register(
    Title, Tooltip, Legend,
    LineElement, PointElement,
    CategoryScale, LinearScale,
    ArcElement, BarElement,
);

const props = defineProps({
    kpis: Object,
    evolutionJournaliere: Array,
    repartitionTypes: Array,
    performanceAgents: Array,
    filtres: Object,
});

const form = useForm({
    debut: props.filtres.debut,
    fin: props.filtres.fin,
});

const filtrer = () => {
    form.get(route('rapports.index'));
};

const formatFCFA = (montant) => {
    return new Intl.NumberFormat('fr-FR').format(montant || 0) + ' FCFA';
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
    });
};

const palette = [
    '#10B981', '#3B82F6', '#F59E0B', '#EF4444', '#8B5CF6',
    '#EC4899', '#14B8A6', '#F97316', '#6366F1', '#84CC16',
];

const dataEvolution = computed(() => ({
    labels: props.evolutionJournaliere.map(j => formatDate(j.date)),
    datasets: [
        {
            label: 'Recettes (FCFA)',
            data: props.evolutionJournaliere.map(j => Number(j.recettes)),
            borderColor: '#10B981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 4,
            pointHoverRadius: 6,
        },
    ],
}));

const optionsEvolution = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (ctx) => formatFCFA(ctx.parsed.y),
            },
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                callback: (val) => new Intl.NumberFormat('fr-FR').format(val),
            },
        },
    },
};

const dataTypes = computed(() => ({
    labels: props.repartitionTypes.map(t => t.libelle),
    datasets: [
        {
            data: props.repartitionTypes.map(t => t.nombre),
            backgroundColor: palette,
            borderWidth: 2,
            borderColor: '#fff',
        },
    ],
}));

const optionsTypes = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'right' },
    },
};

const dataAgents = computed(() => ({
    labels: props.performanceAgents.map(a => a.name),
    datasets: [
        {
            label: 'Recettes (FCFA)',
            data: props.performanceAgents.map(a => Number(a.total_recettes)),
            backgroundColor: '#3B82F6',
            borderRadius: 8,
        },
    ],
}));

const optionsAgents = {
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (ctx) => formatFCFA(ctx.parsed.x),
            },
        },
    },
    scales: {
        x: {
            beginAtZero: true,
            ticks: {
                callback: (val) => new Intl.NumberFormat('fr-FR').format(val),
            },
        },
    },
};
</script>

<template>
    <Head title="Rapports d'activité" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Rapports d'activité
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- ===== Filtres de date ===== -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <div class="flex flex-col sm:flex-row gap-4 items-end">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Du</label>
                            <input
                                v-model="form.debut"
                                type="date"
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition"
                            />
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Au</label>
                            <input
                                v-model="form.fin"
                                type="date"
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition"
                            />
                        </div>
                        <button
                            @click="filtrer"
                            class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-semibold transition"
                        >
                            Appliquer
                        </button>
                    </div>
                </div>

                <!-- ===== KPIs (3 cartes au lieu de 4 — annulations retirées) ===== -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl p-5 text-white shadow-lg">
                        <p class="text-emerald-100 text-sm font-medium">Recettes totales</p>
                        <p class="text-3xl font-bold mt-2">{{ formatFCFA(kpis.totalRecettes) }}</p>
                    </div>
                    <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl p-5 text-white shadow-lg">
                        <p class="text-blue-100 text-sm font-medium">Tickets vendus</p>
                        <p class="text-3xl font-bold mt-2">{{ kpis.totalTickets }}</p>
                    </div>
                    <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl p-5 text-white shadow-lg">
                        <p class="text-amber-100 text-sm font-medium">Ticket moyen</p>
                        <p class="text-3xl font-bold mt-2">{{ formatFCFA(kpis.ticketMoyen) }}</p>
                    </div>
                </div>

                <!-- ===== Graphique évolution ===== -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <h3 class="font-semibold text-gray-900 mb-4">Évolution des recettes</h3>
                    <div v-if="evolutionJournaliere.length > 0" class="h-72">
                        <Line :data="dataEvolution" :options="optionsEvolution" />
                    </div>
                    <div v-else class="h-72 flex items-center justify-center text-gray-400">
                        Aucune donnée pour cette période
                    </div>
                </div>

                <!-- ===== Graphiques côte à côte (répartition + performance) ===== -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <h3 class="font-semibold text-gray-900 mb-4">Répartition par prestation</h3>
                        <div v-if="repartitionTypes.length > 0" class="h-72">
                            <Doughnut :data="dataTypes" :options="optionsTypes" />
                        </div>
                        <div v-else class="h-72 flex items-center justify-center text-gray-400">
                            Aucune donnée
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <h3 class="font-semibold text-gray-900 mb-4">Performance par caissier</h3>
                        <div v-if="performanceAgents.length > 0" :class="performanceAgents.length > 5 ? 'h-96' : 'h-72'">
                            <Bar :data="dataAgents" :options="optionsAgents" />
                        </div>
                        <div v-else class="h-72 flex items-center justify-center text-gray-400">
                            Aucune donnée
                        </div>
                    </div>
                </div>

                <!-- ===== Tableau détaillé ===== -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-900">Détail par prestation</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Prestation</th>
                                    <th class="text-right px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Nombre</th>
                                    <th class="text-right px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Total</th>
                                    <th class="text-right px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Moyenne</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="type in repartitionTypes" :key="type.libelle" class="hover:bg-gray-50 transition">
                                    <td class="px-5 py-3 text-sm font-medium text-gray-900">{{ type.libelle }}</td>
                                    <td class="px-5 py-3 text-sm text-gray-700 text-right">{{ type.nombre }}</td>
                                    <td class="px-5 py-3 text-sm font-semibold text-emerald-600 text-right">{{ formatFCFA(type.total) }}</td>
                                    <td class="px-5 py-3 text-sm text-gray-700 text-right">{{ formatFCFA(type.total / type.nombre) }}</td>
                                </tr>
                                <tr v-if="repartitionTypes.length === 0">
                                    <td colspan="4" class="px-5 py-8 text-center text-gray-400">Aucune donnée</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
