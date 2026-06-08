<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-emerald-50 flex flex-col">

        <!-- ===== En-tête simple avec logo PS Boulal ===== -->
        <div class="flex flex-col items-center pt-10 sm:pt-16 px-4">
            <Link href="/" class="flex items-center gap-3 mb-2">
                <div class="w-14 h-14 rounded-2xl bg-emerald-600 flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-emerald-200">
                    PS
                </div>
                <div class="text-left">
                    <p class="text-xl font-bold text-gray-900 leading-tight">PS Boulal</p>
                    <p class="text-sm text-gray-500 leading-tight">Poste de Santé de Boulal</p>
                </div>
            </Link>
        </div>

        <!-- ===== Carte centrale ===== -->
        <div class="flex-1 flex items-start sm:items-center justify-center px-4 pt-8 sm:pt-4 pb-8">
            <div class="w-full sm:max-w-md bg-white px-6 py-8 sm:px-8 shadow-lg rounded-2xl border border-gray-100">
                <slot />
            </div>
        </div>

        <!-- ===== Pied de page ===== -->
        <footer class="bg-transparent py-4 px-4">
            <div class="max-w-7xl mx-auto text-center">
                <p class="text-xs text-gray-500">
                    © 2026 Poste de Santé de Boulal · <span class="text-gray-400">Application développée par Salla NGOM</span>
                </p>
            </div>
        </footer>
    </div>
</template>
