<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    agent: Object,
});

const page = usePage();
const userActuel = computed(() => page.props.auth.user);

const isEdit = computed(() => !!props.agent);
const estProprePropfile = computed(() => isEdit.value && props.agent?.id === userActuel.value.id);

// === Domaine par défaut pour l'autocomplétion ===
const DOMAINE_PAR_DEFAUT = 'posteboulal.sn';

const form = useForm({
    name: props.agent?.name || '',
    email: props.agent?.email || '',
    password: '',
    password_confirmation: '',
    role: props.agent?.role || 'caissier',
    actif: props.agent?.actif ?? true,
});

// === Logique d'autocomplétion email ===
const completerEmail = () => {
    // Si vide, ne rien faire
    if (!form.email || form.email.trim() === '') return;

    const emailNettoye = form.email.trim().toLowerCase();

    // Si déjà un email complet valide (avec @ et un domaine), ne pas modifier
    if (emailNettoye.includes('@') && emailNettoye.split('@')[1].length > 0) {
        return;
    }

    // Si juste le préfixe (ex: "mariama"), ajouter @posteboulal.sn
    if (!emailNettoye.includes('@')) {
        form.email = `${emailNettoye}@${DOMAINE_PAR_DEFAUT}`;
        return;
    }

    // Si "mariama@" sans domaine, compléter avec le domaine par défaut
    if (emailNettoye.endsWith('@')) {
        form.email = `${emailNettoye}${DOMAINE_PAR_DEFAUT}`;
        return;
    }
};

// === Aperçu en temps réel pendant la saisie ===
const apercuEmail = computed(() => {
    if (!form.email || form.email.trim() === '') return '';
    const emailNettoye = form.email.trim().toLowerCase();
    if (emailNettoye.includes('@') && emailNettoye.split('@')[1].length > 0) {
        return ''; // Email déjà complet, pas d'aperçu nécessaire
    }
    if (emailNettoye.endsWith('@')) {
        return `${emailNettoye}${DOMAINE_PAR_DEFAUT}`;
    }
    if (!emailNettoye.includes('@')) {
        return `${emailNettoye}@${DOMAINE_PAR_DEFAUT}`;
    }
    return '';
});

const submit = () => {
    // Compléter l'email avant la soumission (sécurité)
    completerEmail();

    if (isEdit.value) {
        form.put(route('agents.update', props.agent.id), {
            onSuccess: () => form.reset('password', 'password_confirmation'),
        });
    } else {
        form.post(route('agents.store'));
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Modifier l\'agent' : 'Nouvel agent'" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ isEdit ? 'Modifier l\'agent' : 'Nouvel agent' }}
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

                    <div class="bg-gradient-to-r from-emerald-600 to-teal-600 p-6 text-white">
                        <h1 class="text-2xl font-bold mb-1">
                            {{ isEdit ? 'Modifier l\'agent' : 'Créer un nouvel agent' }}
                        </h1>
                        <p class="text-emerald-100">
                            {{ isEdit ? 'Modifiez les informations de cet utilisateur' : 'Définissez les accès du nouvel agent' }}
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="p-6 space-y-5">

                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Nom complet <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                autofocus
                                placeholder="Ex: Aminata DIOP"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                Email <span class="text-red-500">*</span>
                            </label>

                            <!-- ===== Champ email avec autocomplétion ===== -->
                            <div class="relative">
                                <input
                                    id="email"
                                    v-model="form.email"
                                    @blur="completerEmail"
                                    @keydown.tab="completerEmail"
                                    type="text"
                                    required
                                    placeholder="mariama (ou email complet)"
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition"
                                />
                            </div>

                            <!-- ===== Aperçu de l'email final ===== -->
                            <div v-if="apercuEmail" class="mt-2 p-2 bg-emerald-50 border border-emerald-200 rounded-lg flex items-center gap-2">
                                <span class="text-xs font-bold text-emerald-700">→</span>
                                <span class="text-sm text-emerald-900">
                                    Sera enregistré comme : <strong>{{ apercuEmail }}</strong>
                                </span>
                            </div>

                            <p class="text-xs text-gray-500 mt-1">
                                Tapez juste le prénom (ex: « mariama ») et appuyez sur Tab. Le domaine <strong>@{{ DOMAINE_PAR_DEFAUT }}</strong> est ajouté automatiquement.
                            </p>

                            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                        </div>

                        <div v-if="!isEdit">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                        Mot de passe <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="password"
                                        v-model="form.password"
                                        type="password"
                                        required
                                        placeholder="8 caractères minimum"
                                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition"
                                    />
                                    <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                                </div>
                                <div>
                                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                                        Confirmation <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        type="password"
                                        required
                                        placeholder="Retapez le mot de passe"
                                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition"
                                    />
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Communiquez ces identifiants à l'agent. Il pourra changer son mot de passe via la page Profil après connexion.</p>
                        </div>

                        <div v-if="isEdit" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <p class="text-sm text-blue-900">
                                <span class="font-semibold">Mot de passe :</span> Pour réinitialiser le mot de passe de cet agent, retournez à la liste et cliquez sur « Mot de passe ».
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Rôle <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                                <label class="cursor-pointer">
                                    <input
                                        type="radio"
                                        value="caissier"
                                        v-model="form.role"
                                        :disabled="estProprePropfile"
                                        class="sr-only peer"
                                    />
                                    <div class="p-3 border-2 border-gray-200 rounded-xl peer-checked:border-emerald-500 peer-checked:bg-emerald-50 transition hover:border-emerald-300 text-center" :class="{ 'opacity-50 cursor-not-allowed': estProprePropfile }">
                                        <p class="font-semibold text-gray-900 text-sm">Caissier</p>
                                        <p class="text-xs text-gray-500 mt-0.5">Tickets accueil</p>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input
                                        type="radio"
                                        value="pharmacien"
                                        v-model="form.role"
                                        :disabled="estProprePropfile"
                                        class="sr-only peer"
                                    />
                                    <div class="p-3 border-2 border-gray-200 rounded-xl peer-checked:border-purple-500 peer-checked:bg-purple-50 transition hover:border-purple-300 text-center" :class="{ 'opacity-50 cursor-not-allowed': estProprePropfile }">
                                        <p class="font-semibold text-gray-900 text-sm">Pharmacien</p>
                                        <p class="text-xs text-gray-500 mt-0.5">Vente médicaments</p>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input
                                        type="radio"
                                        value="superviseur"
                                        v-model="form.role"
                                        :disabled="estProprePropfile"
                                        class="sr-only peer"
                                    />
                                    <div class="p-3 border-2 border-gray-200 rounded-xl peer-checked:border-blue-500 peer-checked:bg-blue-50 transition hover:border-blue-300 text-center" :class="{ 'opacity-50 cursor-not-allowed': estProprePropfile }">
                                        <p class="font-semibold text-gray-900 text-sm">Superviseur</p>
                                        <p class="text-xs text-gray-500 mt-0.5">Lecture seule</p>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input
                                        type="radio"
                                        value="admin"
                                        v-model="form.role"
                                        :disabled="estProprePropfile"
                                        class="sr-only peer"
                                    />
                                    <div class="p-3 border-2 border-gray-200 rounded-xl peer-checked:border-pink-500 peer-checked:bg-pink-50 transition hover:border-pink-300 text-center" :class="{ 'opacity-50 cursor-not-allowed': estProprePropfile }">
                                        <p class="font-semibold text-gray-900 text-sm">Administrateur</p>
                                        <p class="text-xs text-gray-500 mt-0.5">Accès complet</p>
                                    </div>
                                </label>
                            </div>
                            <p v-if="estProprePropfile" class="text-xs text-amber-600 mt-2">Vous ne pouvez pas modifier votre propre rôle.</p>
                            <p v-if="form.errors.role" class="mt-1 text-sm text-red-600">{{ form.errors.role }}</p>
                        </div>

                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <label class="flex items-start gap-3" :class="estProprePropfile ? 'cursor-not-allowed' : 'cursor-pointer'">
                                <input
                                    v-model="form.actif"
                                    type="checkbox"
                                    :disabled="estProprePropfile"
                                    class="mt-0.5 w-5 h-5 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500 disabled:opacity-50"
                                />
                                <div>
                                    <span class="block font-medium text-gray-900">Compte actif</span>
                                    <span class="block text-sm text-gray-600 mt-0.5">
                                        Si décoché, l'agent ne pourra plus se connecter.
                                    </span>
                                    <span v-if="estProprePropfile" class="block text-xs text-amber-600 mt-1">
                                        Vous ne pouvez pas désactiver votre propre compte.
                                    </span>
                                </div>
                            </label>
                            <p v-if="form.errors.actif" class="mt-2 text-sm text-red-600">{{ form.errors.actif }}</p>
                        </div>

                        <div class="flex gap-3 pt-4 border-t border-gray-100">
                            <Link
                                :href="route('agents.index')"
                                class="flex-1 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 text-center rounded-lg font-medium transition"
                            >
                                Annuler
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex-1 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-400 text-white rounded-lg font-semibold shadow-lg shadow-emerald-200 transition"
                            >
                                <span v-if="!form.processing">{{ isEdit ? 'Enregistrer' : 'Créer l\'agent' }}</span>
                                <span v-else>Enregistrement...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
