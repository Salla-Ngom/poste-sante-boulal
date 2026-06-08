<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>

    <Head title="PS Boulal" />

    <div class="min-h-screen bg-white">
        <!-- ===== Navigation ===== -->
        <nav class="bg-white border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center gap-2">
                        <div class="w-9 h-9 rounded-lg bg-emerald-600 flex items-center justify-center text-white font-bold">
                            PS
                        </div>
                        <span class="text-lg font-semibold text-gray-900">PS Boulal</span>
                    </div>
                    <div class="flex items-center gap-3" v-if="canLogin">
                        <Link :href="route('login')"
                            class="text-sm bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 rounded-lg font-medium shadow-md shadow-emerald-200 transition">
                        Connexion
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- ===== Section Héros ===== -->
        <section class="bg-gradient-to-b from-emerald-50 to-white py-16 sm:py-20 lg:py-24 px-4">
            <div class="max-w-5xl mx-auto text-center">
                <span class="inline-block px-4 py-1 bg-white text-emerald-700 rounded-full text-sm font-medium border border-emerald-200 mb-6">
                    Poste de Santé de Boulal
                </span>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-bold text-gray-900 leading-tight mb-6">
                    La caisse intelligente
                    <br />
                    <span class="text-emerald-600">au service de Boulal</span>
                </h1>
                <p class="text-lg sm:text-xl text-gray-600 max-w-3xl mx-auto mb-8 leading-relaxed">
                    Vente de tickets, suivi des recettes et gestion de la pharmacie, le tout dans une application simple,
                    sécurisée et adaptée au quotidien du poste.
                </p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <Link v-if="canLogin" :href="route('login')"
                        class="px-8 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium shadow-lg shadow-emerald-200 transition">
                    Accéder à mon compte
                    </Link>
                    <a href="#fonctionnalites"
                        class="px-8 py-3 bg-white hover:bg-gray-50 text-gray-700 rounded-lg font-medium border border-gray-200 transition">
                        Découvrir les fonctionnalités
                    </a>
                </div>
            </div>
        </section>

        <!-- ===== Section Fonctionnalités ===== -->
        <section id="fonctionnalites" class="py-16 sm:py-20 lg:py-24 px-4">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-12">
                    <p class="text-sm font-semibold text-emerald-600 tracking-wide uppercase mb-2">
                        Fonctionnalités
                    </p>
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
                        Conçue pour le quotidien du poste
                    </h2>
                    <p class="text-gray-600 max-w-2xl mx-auto text-lg">
                        Une solution complète pour la caisse, la pharmacie et le suivi de l'activité.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-xl border border-gray-100 hover:border-emerald-200 hover:shadow-lg transition">
                        <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center mb-4 text-emerald-600 font-bold text-xl">
                            ✓
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Vente de tickets</h3>
                        <p class="text-gray-600 text-sm leading-relaxed">
                            Numérotation automatique journalière et génération instantanée de reçus pour les patients.
                        </p>
                    </div>

                    <div class="bg-white p-6 rounded-xl border border-gray-100 hover:border-blue-200 hover:shadow-lg transition">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4 text-blue-600 font-bold text-xl">
                            ≡
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Suivi des recettes</h3>
                        <p class="text-gray-600 text-sm leading-relaxed">
                            Tableaux de bord journaliers et mensuels en temps réel pour piloter l'activité du poste.
                        </p>
                    </div>

                    <div class="bg-white p-6 rounded-xl border border-gray-100 hover:border-amber-200 hover:shadow-lg transition">
                        <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center mb-4 text-amber-600 font-bold text-xl">
                            ★
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Audit complet</h3>
                        <p class="text-gray-600 text-sm leading-relaxed">
                            Journal de toutes les actions et historique des annulations pour un contrôle total.
                        </p>
                    </div>

                    <div class="bg-white p-6 rounded-xl border border-gray-100 hover:border-purple-200 hover:shadow-lg transition">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4 text-purple-600 font-bold text-xl">
                            ◉
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Gestion pharmacie</h3>
                        <p class="text-gray-600 text-sm leading-relaxed">
                            Stock en temps réel, alertes automatiques de rupture, suivi des livraisons fournisseurs.
                        </p>
                    </div>

                    <div class="bg-white p-6 rounded-xl border border-gray-100 hover:border-pink-200 hover:shadow-lg transition">
                        <div class="w-12 h-12 bg-pink-100 rounded-lg flex items-center justify-center mb-4 text-pink-600 font-bold text-xl">
                            ◆
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Rapports de caisse</h3>
                        <p class="text-gray-600 text-sm leading-relaxed">
                            Génération automatique des rapports signés en fin de journée, prêts à être archivés.
                        </p>
                    </div>

                    <div class="bg-white p-6 rounded-xl border border-gray-100 hover:border-teal-200 hover:shadow-lg transition">
                        <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center mb-4 text-teal-600 font-bold text-xl">
                            ⚿
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Sécurité maximale</h3>
                        <p class="text-gray-600 text-sm leading-relaxed">
                            Données chiffrées, sauvegardes automatiques et accès contrôlé par identifiants individuels.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== Section Statistiques ===== -->
        <section class="bg-gray-50 py-16 px-4">
            <div class="max-w-6xl mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                    <div>
                        <p class="text-4xl sm:text-5xl font-bold text-emerald-600 mb-2">100%</p>
                        <p class="text-sm text-gray-600">Traçabilité des opérations</p>
                    </div>
                    <div>
                        <p class="text-4xl sm:text-5xl font-bold text-blue-600 mb-2">5 min</p>
                        <p class="text-sm text-gray-600">Pour vendre un ticket</p>
                    </div>
                    <div>
                        <p class="text-4xl sm:text-5xl font-bold text-amber-600 mb-2">24/7</p>
                        <p class="text-sm text-gray-600">Support disponible</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== Section CTA ===== -->
        <section class="bg-emerald-600 py-16 px-4">
            <div class="max-w-4xl mx-auto text-center">
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-4">
                    Prêt à démarrer votre journée ?
                </h2>
                <p class="text-emerald-50 mb-8 text-lg sm:text-xl">
                    Connectez-vous à votre espace pour accéder à votre caisse.
                </p>
                <Link v-if="canLogin" :href="route('login')"
                    class="inline-block px-8 py-3 bg-white hover:bg-gray-50 text-emerald-700 rounded-lg font-semibold shadow-lg transition">
                Se connecter
                </Link>
            </div>
        </section>

        <!-- ===== Footer ===== -->
        <footer class="bg-gray-900 text-gray-400 py-12 px-4">
            <div class="max-w-7xl mx-auto">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-emerald-600 flex items-center justify-center text-white font-bold text-sm">
                            PS
                        </div>
                        <span class="text-white font-semibold">PS Boulal</span>
                    </div>
                    <p class="text-sm text-center md:text-right">
                        © 2026 Poste de Santé de Boulal · <span class="text-gray-500">Application développée par Salla NGOM</span>
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
