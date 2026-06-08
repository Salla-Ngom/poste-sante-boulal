<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>

    <Head title="Connexion - PS Boulal" />

    <div class="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-emerald-50 flex flex-col">

        <!-- ===== Contenu principal centré ===== -->
        <div class="flex-1 flex items-center justify-center px-4 py-12">
            <div class="max-w-md w-full">

                <!-- Logo et titre Boulal (NON cliquable - reste sur la page) -->
                <div class="text-center mb-8">
                    <div class="inline-flex items-center gap-3 mb-6">
                        <div
                            class="w-14 h-14 rounded-2xl bg-emerald-600 flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-emerald-200">
                            PS
                        </div>
                        <div class="text-left">
                            <p class="text-xl font-bold text-gray-900 leading-tight">PS Boulal</p>
                            <p class="text-sm text-gray-500 leading-tight">Poste de Santé de Boulal</p>
                        </div>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">
                        Bienvenue
                    </h1>
                    <p class="text-gray-600 text-sm">
                        Connectez-vous pour accéder à votre caisse
                    </p>
                </div>

                <!-- Carte du formulaire -->
                <div class="bg-white rounded-2xl shadow-xl shadow-gray-100 border border-gray-100 p-8">

                    <div v-if="status"
                        class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-lg">
                        {{ status }}
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                Adresse email
                            </label>
                            <input id="email" v-model="form.email" type="email" required autofocus
                                autocomplete="username" placeholder="vous@posteboulal.sn"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition" />
                            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                Mot de passe
                            </label>
                            <input id="password" v-model="form.password" type="password" required
                                autocomplete="current-password" placeholder="••••••••"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition" />
                            <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="flex items-center cursor-pointer">
                                <input v-model="form.remember" type="checkbox"
                                    class="w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500" />
                                <span class="ml-2 text-sm text-gray-600">Se souvenir de moi</span>
                            </label>

                            <Link v-if="canResetPassword" :href="route('password.request')"
                                class="text-sm text-emerald-600 hover:text-emerald-700 font-medium">
                            Mot de passe oublié ?
                            </Link>
                        </div>

                        <button type="submit" :disabled="form.processing"
                            class="w-full bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-400 text-white font-semibold py-3 rounded-lg shadow-lg shadow-emerald-200 transition flex items-center justify-center gap-2">
                            <span v-if="!form.processing">Se connecter</span>
                            <span v-else class="flex items-center gap-2">
                                <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                Connexion en cours...
                            </span>
                        </button>
                    </form>
                </div>

                <!-- Message d'aide (remplace le lien d'inscription) -->
                <p class="text-center text-sm text-gray-500 mt-6">
                    Identifiants oubliés ? Contactez l'administrateur du poste.
                </p>
            </div>
        </div>

        <!-- ===== Footer ===== -->
        <footer class="py-4 px-4">
            <p class="text-center text-xs text-gray-500">
                © 2026 Poste de Santé de Boulal · <span class="text-gray-400">Application développée par Salla NGOM</span>
            </p>
        </footer>
    </div>
</template>
