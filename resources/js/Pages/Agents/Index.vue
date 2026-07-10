<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    agents: Array,
});

const page = usePage();
const userActuel = computed(() => page.props.auth.user);

const formatFCFA = (montant) => {
    return new Intl.NumberFormat('fr-FR').format(montant || 0) + ' FCFA';
};

// Gestion des modales
const agentATogglet = ref(null);
const agentResetPwd = ref(null);
const formReset = useForm({
    password: '',
    password_confirmation: '',
});

const ouvrirToggle = (agent) => {
    agentATogglet.value = agent;
};

const fermerToggle = () => {
    agentATogglet.value = null;
};

const confirmerToggle = () => {
    router.post(route('agents.toggle', agentATogglet.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => fermerToggle(),
    });
};

const ouvrirReset = (agent) => {
    agentResetPwd.value = agent;
    formReset.reset();
    formReset.clearErrors();
};

const fermerReset = () => {
    agentResetPwd.value = null;
    formReset.reset();
};

const confirmerReset = () => {
    formReset.post(route('agents.reset-password', agentResetPwd.value.id), {
        preserveScroll: true,
        onSuccess: () => fermerReset(),
    });
};

// Couleur du badge selon le rôle
const roleBadge = (role) => {
    const badges = {
        superadmin: 'bg-gray-900 text-white',
        admin: 'bg-pink-100 text-pink-700',
        superviseur: 'bg-blue-100 text-blue-700',
        pharmacien: 'bg-purple-100 text-purple-700',
        caissier: 'bg-emerald-100 text-emerald-700',
    };
    return badges[role] || badges.caissier;
};

const roleLibelle = (role) => {
    const libelles = {
        superadmin: 'Superadmin',
        admin: 'Administrateur',
        superviseur: 'Superviseur',
        pharmacien: 'Pharmacien',
        caissier: 'Caissier',
    };
    return libelles[role] || role;
};
</script>

<template>
    <Head title="Agents" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Gestion des agents
                </h2>
                <Link
                    :href="route('agents.create')"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-semibold shadow-md shadow-emerald-200 transition flex items-center gap-2"
                >
                    <span class="text-lg leading-none">+</span>
                    Nouvel agent
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <p class="text-sm text-blue-900">
                        <span class="font-semibold">À savoir :</span>
                        Vous pouvez créer des comptes caissier, superviseur ou administrateur.
                        Un agent désactivé ne peut plus se connecter, mais ses ventes passées restent dans l'historique.
                    </p>
                </div>

                <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Agent</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Rôle</th>
                                    <th class="text-center px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Statut</th>
                                    <th class="text-right px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Ventes</th>
                                    <th class="text-right px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Recettes</th>
                                    <th class="text-right px-5 py-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="agent in agents" :key="agent.id" class="hover:bg-gray-50 transition" :class="{ 'opacity-60': !agent.actif }">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center text-white font-semibold">
                                                {{ agent.name.charAt(0).toUpperCase() }}
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-900">{{ agent.name }}</p>
                                                <p class="text-xs text-gray-500">{{ agent.email }}</p>
                                            </div>
                                            <span v-if="agent.id === userActuel.id" class="text-xs px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full font-medium">
                                                Vous
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="inline-block px-2.5 py-1 text-xs font-semibold rounded-full" :class="roleBadge(agent.role)">
                                            {{ roleLibelle(agent.role) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span v-if="agent.actif" class="inline-block px-3 py-1 bg-emerald-100 text-emerald-700 text-xs font-semibold rounded-full">
                                            Actif
                                        </span>
                                        <span v-else class="inline-block px-3 py-1 bg-gray-100 text-gray-600 text-xs font-semibold rounded-full">
                                            Désactivé
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <span class="text-sm font-medium text-gray-700">{{ agent.total_ventes || 0 }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <span class="text-sm font-semibold text-emerald-600">{{ formatFCFA(agent.total_recettes) }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <Link
                                                :href="route('agents.edit', agent.id)"
                                                class="text-emerald-600 hover:text-emerald-700 text-sm font-medium"
                                            >
                                                Modifier
                                            </Link>
                                            <button
                                                v-if="userActuel.role === 'superadmin'"
                                                @click="ouvrirReset(agent)"
                                                class="text-blue-600 hover:text-blue-700 text-sm font-medium"
                                            >
                                                Mot de passe
                                            </button>
                                            <button
                                                v-if="userActuel.role === 'superadmin' && agent.id !== userActuel.id"
                                                @click="ouvrirToggle(agent)"
                                                class="text-sm font-medium"
                                                :class="agent.actif ? 'text-red-600 hover:text-red-700' : 'text-blue-600 hover:text-blue-700'"
                                            >
                                                {{ agent.actif ? 'Désactiver' : 'Réactiver' }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="agents.length === 0">
                                    <td colspan="6" class="px-5 py-12 text-center">
                                        <p class="text-gray-500 font-medium mb-1">Aucun agent</p>
                                        <p class="text-sm text-gray-400">Créez votre premier agent avec le bouton en haut.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- ===== Modal toggle activation ===== -->
        <div v-if="agentATogglet" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                        {{ agentATogglet.actif ? 'Désactiver' : 'Réactiver' }} l'agent ?
                    </h3>
                    <p class="text-sm text-gray-600">
                        <span class="font-semibold">{{ agentATogglet.name }}</span>
                        <span v-if="agentATogglet.actif">
                            ne pourra plus se connecter à l'application. Ses ventes passées restent dans l'historique.
                        </span>
                        <span v-else>
                            pourra à nouveau se connecter à l'application.
                        </span>
                    </p>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex gap-3">
                    <button @click="fermerToggle" class="flex-1 px-4 py-2 bg-white hover:bg-gray-100 border border-gray-200 text-gray-700 rounded-lg font-medium transition text-sm">
                        Annuler
                    </button>
                    <button @click="confirmerToggle" class="flex-1 px-4 py-2 text-white rounded-lg font-semibold transition text-sm" :class="agentATogglet.actif ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700'">
                        Confirmer
                    </button>
                </div>
            </div>
        </div>

        <!-- ===== Modal réinitialisation mot de passe ===== -->
        <div v-if="agentResetPwd" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-5 text-white">
                    <h3 class="text-lg font-bold">Réinitialiser le mot de passe</h3>
                    <p class="text-sm text-blue-100 mt-1">{{ agentResetPwd.name }}</p>
                </div>
                <form @submit.prevent="confirmerReset" class="p-5 space-y-4">

                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                        <p class="text-xs text-amber-900">
                            <span class="font-semibold">Attention :</span> Communiquez le nouveau mot de passe à l'agent par un canal sécurisé. Demandez-lui de le changer dès sa première connexion via la page Profil.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nouveau mot de passe</label>
                        <input
                            v-model="formReset.password"
                            type="password"
                            required
                            placeholder="Au moins 8 caractères"
                            class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition text-sm"
                        />
                        <p v-if="formReset.errors.password" class="mt-1 text-xs text-red-600">
                            {{ formReset.errors.password }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirmation</label>
                        <input
                            v-model="formReset.password_confirmation"
                            type="password"
                            required
                            placeholder="Retapez le mot de passe"
                            class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition text-sm"
                        />
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="fermerReset" class="flex-1 px-4 py-2 bg-white hover:bg-gray-100 border border-gray-200 text-gray-700 rounded-lg font-medium transition text-sm">
                            Annuler
                        </button>
                        <button type="submit" :disabled="formReset.processing" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white rounded-lg font-semibold transition text-sm">
                            <span v-if="!formReset.processing">Réinitialiser</span>
                            <span v-else>Mise à jour...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
