<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    status: Number,
    message: String, // message métier (403 uniquement)
});

const page = usePage();
const utilisateurConnecte = computed(() => page.props.auth?.user ?? null);

const contenus = {
    403: {
        emoji: '🔒',
        titre: 'Accès refusé',
        description: "Vous n'avez pas les droits nécessaires pour effectuer cette action.",
    },
    404: {
        emoji: '🔍',
        titre: 'Page introuvable',
        description: "Cette page n'existe pas ou a été déplacée.",
    },
    405: {
        emoji: '⛔',
        titre: 'Action non autorisée',
        description: "Cette opération n'est pas permise sur cette page.",
    },
    429: {
        emoji: '⏳',
        titre: 'Trop de tentatives',
        description: 'Patientez un instant avant de réessayer.',
    },
    500: {
        emoji: '⚠️',
        titre: 'Erreur interne',
        description: "Un problème technique est survenu. Si cela persiste, contactez le support technique.",
    },
    503: {
        emoji: '🛠️',
        titre: 'Maintenance en cours',
        description: "L'application est temporairement indisponible. Réessayez dans quelques minutes.",
    },
};

const contenu = computed(() => contenus[props.status] || contenus[500]);

const retourArriere = () => window.history.back();
</script>

<template>
    <Head :title="`${status} — ${contenu.titre}`" />

    <div class="min-h-screen bg-gray-100 flex items-center justify-center p-4">
        <div class="max-w-lg w-full">

            <!-- Carte principale -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">

                <!-- Bandeau code en grand -->
                <div class="relative bg-emerald-600 px-8 py-10 text-center overflow-hidden">
                    <p class="absolute inset-0 flex items-center justify-center text-[9rem] font-black text-emerald-500/40 select-none leading-none">
                        {{ status }}
                    </p>
                    <div class="relative">
                        <p class="text-5xl mb-2">{{ contenu.emoji }}</p>
                        <h1 class="text-2xl font-bold text-white">{{ contenu.titre }}</h1>
                    </div>
                </div>

                <!-- Corps -->
                <div class="p-8 text-center">
                    <p class="text-gray-600 mb-4">{{ contenu.description }}</p>

                    <!-- Message métier (403 : messages des abort() du projet) -->
                    <div
                        v-if="message"
                        class="mb-6 px-4 py-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800"
                    >
                        {{ message }}
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                        <button
                            @click="retourArriere"
                            class="px-5 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-medium transition"
                        >
                            ← Page précédente
                        </button>

                        <Link
                            v-if="utilisateurConnecte"
                            :href="route('dashboard')"
                            class="px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-semibold transition"
                        >
                            Tableau de bord
                        </Link>
                        <Link
                            v-else
                            :href="route('login')"
                            class="px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-semibold transition"
                        >
                            Se connecter
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Pied de page -->
            <p class="text-center text-xs text-gray-400 mt-6">
                CaissePro Santé — Poste de Santé de Boulal
            </p>
        </div>
    </div>
</template>
