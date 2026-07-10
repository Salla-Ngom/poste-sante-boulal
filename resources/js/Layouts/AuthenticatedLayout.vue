<script setup>
import { ref, computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";

const showingNavigationDropdown = ref(false);
const showingUserMenu = ref(false);
const showingMoreMenu = ref(false);
const showingMedicamentsMenu = ref(false);

let medicamentsHoverTimeout = null;

const page = usePage();
const user = computed(() => page.props.auth.user);

const roleBadge = computed(() => {
    const badges = {
        superadmin: { label: "Superadmin", bg: "bg-gray-900", text: "text-white" },
        admin: { label: "Administrateur", bg: "bg-pink-100", text: "text-pink-700" },
        superviseur: { label: "Superviseur", bg: "bg-blue-100", text: "text-blue-700" },
        pharmacien: { label: "Pharmacien", bg: "bg-purple-100", text: "text-purple-700" },
        caissier: { label: "Caissier", bg: "bg-emerald-100", text: "text-emerald-700" },
    };
    return badges[user.value.role] || badges.caissier;
});

const navLinks = computed(() => {
    const links = [
        { name: "Dashboard", route: "dashboard", icon: "D", roles: ["superadmin", "admin", "superviseur", "caissier", "pharmacien"] },
        { name: "Vendre", route: "tickets.vendre", icon: "+", roles: ["caissier"] },
        { name: "Historique", route: "tickets.index", icon: "H", roles: ["superadmin", "admin", "superviseur", "caissier"] },
        { name: "Vendre méd.", route: "pharmacy.vendre", icon: "V", roles: ["pharmacien"] },
        { name: "Histo. pharmacie", route: "pharmacy.historique", icon: "P", roles: ["superviseur", "pharmacien"] },
    ];
    return links.filter((link) => link.roles.includes(user.value.role));
});

const medicamentsLinks = computed(() => {
    if (!['admin', 'superadmin'].includes(user.value.role)) return [];
    return [
        { name: "Catalogue", route: "medicaments.index", icon: "M", description: "Ajouter et configurer les médicaments" },
        { name: "Stats des ventes", route: "medicaments.stats", icon: "V", description: "Ventes par médicament et période" },
        { name: "Stock pharmacie", route: "stocks.index", icon: "S", description: "Inventaire et mouvements" },
        { name: "Historique pharmacie", route: "pharmacy.historique", icon: "H", description: "Ventes pharmacie réalisées" },
    ];
});

const moreLinks = computed(() => {
    const links = [
        { name: "Rapports", route: "rapports.index", icon: "R", roles: ["superadmin", "admin", "superviseur"] },
        { name: "Stats ventes méd.", route: "medicaments.stats", icon: "V", roles: ["pharmacien", "superviseur"] },
        { name: "Stock", route: "stocks.index", icon: "S", roles: ["pharmacien", "superviseur"] },
        { name: "Agents", route: "agents.index", icon: "A", roles: ["superadmin", "admin"] },
        { name: "Types de tickets", route: "ticket-types.index", icon: "T", roles: ["superadmin", "admin"] },
    ];
    return links.filter((link) => link.roles.includes(user.value.role));
});

const quickAction = computed(() => {
    if (user.value.role === "caissier") return { label: "Vendre", route: "tickets.vendre", color: "emerald" };
    if (user.value.role === "pharmacien") return { label: "Vendre méd.", route: "pharmacy.vendre", color: "purple" };
    return null;
});

const isActive = (routeName) => {
    try { return route().current(routeName); } catch { return false; }
};

const ouvrirMenuMedicaments = () => { clearTimeout(medicamentsHoverTimeout); showingMedicamentsMenu.value = true; };
const fermerMenuMedicamentsAvecDelai = () => { medicamentsHoverTimeout = setTimeout(() => { showingMedicamentsMenu.value = false; }, 200); };
const toggleMenuMedicaments = () => { clearTimeout(medicamentsHoverTimeout); showingMedicamentsMenu.value = !showingMedicamentsMenu.value; };
const fermerMenuMedicaments = () => { clearTimeout(medicamentsHoverTimeout); showingMedicamentsMenu.value = false; };
const fermerMenuPlus = () => { showingMoreMenu.value = false; };
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex flex-col">
        <nav class="bg-white border-b border-gray-200 sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">

                    <!-- Logo + Nom -->
                    <div class="flex items-center">
                        <Link :href="route('dashboard')" class="flex items-center gap-2 mr-6">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center text-white font-bold shadow-md"
                                :class="user.role === 'pharmacien' ? 'bg-purple-600 shadow-purple-200' : 'bg-emerald-600 shadow-emerald-200'">
                                PS
                            </div>
                            <div class="hidden xl:block">
                                <p class="text-sm font-bold text-gray-900 leading-tight">PS Boulal</p>
                                <p class="text-xs text-gray-500 leading-tight">Poste de Santé de Boulal</p>
                            </div>
                        </Link>

                        <div class="hidden lg:flex items-center gap-1">
                            <Link v-for="link in navLinks" :key="link.route" :href="route(link.route)"
                                class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition whitespace-nowrap"
                                :class="isActive(link.route)
                                    ? (user.role === 'pharmacien' ? 'bg-purple-50 text-purple-700' : 'bg-emerald-50 text-emerald-700')
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'">
                                <span class="w-6 h-6 flex items-center justify-center rounded text-xs font-bold flex-shrink-0"
                                    :class="isActive(link.route)
                                        ? (user.role === 'pharmacien' ? 'bg-purple-600 text-white' : 'bg-emerald-600 text-white')
                                        : 'bg-gray-100'">{{ link.icon }}</span>
                                <span>{{ link.name }}</span>
                            </Link>

                            <!-- Sous-menu Médicaments (admin) -->
                            <div v-if="medicamentsLinks.length > 0" @mouseenter="ouvrirMenuMedicaments" @mouseleave="fermerMenuMedicamentsAvecDelai" class="relative">
                                <button @click="toggleMenuMedicaments"
                                    class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition whitespace-nowrap"
                                    :class="showingMedicamentsMenu ? 'bg-cyan-50 text-cyan-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'">
                                    <span class="w-6 h-6 flex items-center justify-center rounded text-xs font-bold flex-shrink-0"
                                        :class="showingMedicamentsMenu ? 'bg-cyan-600 text-white' : 'bg-cyan-100 text-cyan-700'">M</span>
                                    <span>Médicaments</span>
                                    <svg class="w-4 h-4 text-gray-400 transition-transform" :class="showingMedicamentsMenu ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div v-if="showingMedicamentsMenu" @mouseenter="ouvrirMenuMedicaments" @mouseleave="fermerMenuMedicamentsAvecDelai"
                                    class="absolute left-0 mt-1 w-72 bg-white rounded-xl shadow-xl border border-gray-100 z-50 overflow-hidden">
                                    <div class="bg-gradient-to-r from-cyan-50 to-blue-50 px-4 py-3 border-b border-cyan-100">
                                        <p class="text-xs font-bold text-cyan-900 uppercase tracking-wider">Gestion pharmacie</p>
                                    </div>
                                    <div class="py-2">
                                        <Link v-for="link in medicamentsLinks" :key="link.route" :href="route(link.route)" @click="fermerMenuMedicaments"
                                            class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 transition group">
                                            <span class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-bold flex-shrink-0 bg-cyan-100 text-cyan-700 group-hover:bg-cyan-600 group-hover:text-white transition">{{ link.icon }}</span>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-semibold text-gray-900 group-hover:text-cyan-700 transition">{{ link.name }}</p>
                                                <p class="text-xs text-gray-500 mt-0.5">{{ link.description }}</p>
                                            </div>
                                        </Link>
                                    </div>
                                </div>
                            </div>

                            <!-- Menu Plus -->
                            <div v-if="moreLinks.length > 0" class="relative ml-1">
                                <button @click="showingMoreMenu = !showingMoreMenu"
                                    class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition whitespace-nowrap">
                                    <span class="w-6 h-6 flex items-center justify-center rounded text-xs font-bold bg-gray-100">⋯</span>
                                    <span>Plus</span>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div v-if="showingMoreMenu" @click="fermerMenuPlus" class="fixed inset-0 z-40"></div>
                                <div v-if="showingMoreMenu" class="absolute left-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-gray-100 z-50 overflow-hidden">
                                    <div class="py-1">
                                        <Link v-for="link in moreLinks" :key="link.route" :href="route(link.route)" @click="fermerMenuPlus"
                                            class="flex items-center gap-3 px-4 py-2.5 text-sm transition"
                                            :class="isActive(link.route)
                                                ? (user.role === 'pharmacien' ? 'bg-purple-50 text-purple-700' : 'bg-emerald-50 text-emerald-700')
                                                : 'text-gray-700 hover:bg-gray-50'">
                                            <span class="w-7 h-7 flex items-center justify-center rounded text-xs font-bold flex-shrink-0"
                                                :class="isActive(link.route)
                                                    ? (user.role === 'pharmacien' ? 'bg-purple-600 text-white' : 'bg-emerald-600 text-white')
                                                    : 'bg-gray-100'">{{ link.icon }}</span>
                                            <span class="font-medium">{{ link.name }}</span>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Menu user desktop -->
                    <div class="hidden lg:flex items-center gap-3">
                        <Link v-if="quickAction" :href="route(quickAction.route)"
                            class="hidden xl:flex items-center gap-2 px-4 py-2 text-white rounded-lg text-sm font-semibold shadow-md transition whitespace-nowrap"
                            :class="quickAction.color === 'purple' ? 'bg-purple-600 hover:bg-purple-700 shadow-purple-200' : 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-200'">
                            <span class="text-lg leading-none">+</span>
                            {{ quickAction.label }}
                        </Link>

                        <div class="relative">
                            <button @click="showingUserMenu = !showingUserMenu" class="flex items-center gap-3 px-3 py-1.5 rounded-lg hover:bg-gray-50 transition">
                                <div class="w-9 h-9 rounded-full flex items-center justify-center text-white font-semibold"
                                    :class="user.role === 'pharmacien' ? 'bg-gradient-to-br from-purple-500 to-pink-500' : 'bg-gradient-to-br from-emerald-500 to-teal-500'">
                                    {{ user.name.charAt(0).toUpperCase() }}
                                </div>
                                <div class="text-left hidden xl:block">
                                    <p class="text-sm font-semibold text-gray-900 leading-tight whitespace-nowrap">{{ user.name }}</p>
                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium" :class="[roleBadge.bg, roleBadge.text]">{{ roleBadge.label }}</span>
                                </div>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div v-if="showingUserMenu" @click="showingUserMenu = false" class="fixed inset-0 z-40"></div>
                            <div v-if="showingUserMenu" class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-lg border border-gray-100 z-50 overflow-hidden">
                                <div class="p-4 border-b border-gray-100"
                                    :class="user.role === 'pharmacien' ? 'bg-gradient-to-br from-purple-50 to-pink-50' : 'bg-gradient-to-br from-emerald-50 to-teal-50'">
                                    <p class="font-semibold text-gray-900">{{ user.name }}</p>
                                    <p class="text-xs text-gray-600 mt-0.5">{{ user.email }}</p>
                                    <span class="inline-block mt-2 text-xs px-2 py-0.5 rounded-full font-medium" :class="[roleBadge.bg, roleBadge.text]">{{ roleBadge.label }}</span>
                                </div>
                                <div class="py-1">
                                    <Link :href="route('profile.edit')" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition">
                                        <span class="w-7 h-7 bg-gray-100 rounded flex items-center justify-center text-xs font-bold text-gray-600">P</span>
                                        Mon profil
                                    </Link>
                                </div>
                                <div class="py-1 border-t border-gray-100">
                                    <Link :href="route('logout')" method="post" as="button" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition">
                                        <span class="w-7 h-7 bg-red-100 rounded flex items-center justify-center text-xs font-bold">D</span>
                                        Déconnexion
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hamburger -->
                    <div class="flex items-center lg:hidden">
                        <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path v-if="!showingNavigationDropdown" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Menu mobile -->
            <div v-if="showingNavigationDropdown" class="lg:hidden border-t border-gray-100">
                <div class="px-4 py-3" :class="user.role === 'pharmacien' ? 'bg-gradient-to-br from-purple-50 to-pink-50' : 'bg-gradient-to-br from-emerald-50 to-teal-50'">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg"
                            :class="user.role === 'pharmacien' ? 'bg-gradient-to-br from-purple-500 to-pink-500' : 'bg-gradient-to-br from-emerald-500 to-teal-500'">
                            {{ user.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">{{ user.name }}</p>
                            <span class="inline-block mt-0.5 text-xs px-2 py-0.5 rounded-full font-medium" :class="[roleBadge.bg, roleBadge.text]">{{ roleBadge.label }}</span>
                        </div>
                    </div>
                </div>

                <div class="py-2">
                    <Link v-for="link in navLinks" :key="link.route" :href="route(link.route)"
                        class="flex items-center gap-3 px-4 py-3 text-sm font-medium transition"
                        :class="isActive(link.route)
                            ? (user.role === 'pharmacien' ? 'bg-purple-50 text-purple-700 border-l-4 border-purple-600' : 'bg-emerald-50 text-emerald-700 border-l-4 border-emerald-600')
                            : 'text-gray-700 hover:bg-gray-50'">
                        <span class="w-7 h-7 flex items-center justify-center rounded text-xs font-bold"
                            :class="isActive(link.route)
                                ? (user.role === 'pharmacien' ? 'bg-purple-600 text-white' : 'bg-emerald-600 text-white')
                                : 'bg-gray-100'">{{ link.icon }}</span>
                        {{ link.name }}
                    </Link>

                    <div v-if="medicamentsLinks.length > 0">
                        <div class="border-t border-gray-100 my-2"></div>
                        <div class="px-4 py-2 text-xs font-bold text-cyan-700 uppercase tracking-wider bg-cyan-50">Médicaments</div>
                        <Link v-for="link in medicamentsLinks" :key="link.route" :href="route(link.route)"
                            class="flex items-center gap-3 px-4 py-3 text-sm font-medium transition"
                            :class="isActive(link.route) ? 'bg-cyan-50 text-cyan-700 border-l-4 border-cyan-600' : 'text-gray-700 hover:bg-gray-50'">
                            <span class="w-7 h-7 flex items-center justify-center rounded text-xs font-bold"
                                :class="isActive(link.route) ? 'bg-cyan-600 text-white' : 'bg-cyan-100 text-cyan-700'">{{ link.icon }}</span>
                            <div class="flex-1">
                                <div>{{ link.name }}</div>
                                <div class="text-xs text-gray-500">{{ link.description }}</div>
                            </div>
                        </Link>
                    </div>

                    <div v-if="moreLinks.length > 0" class="border-t border-gray-100 my-2"></div>

                    <Link v-for="link in moreLinks" :key="link.route" :href="route(link.route)"
                        class="flex items-center gap-3 px-4 py-3 text-sm font-medium transition"
                        :class="isActive(link.route)
                            ? (user.role === 'pharmacien' ? 'bg-purple-50 text-purple-700 border-l-4 border-purple-600' : 'bg-emerald-50 text-emerald-700 border-l-4 border-emerald-600')
                            : 'text-gray-700 hover:bg-gray-50'">
                        <span class="w-7 h-7 flex items-center justify-center rounded text-xs font-bold"
                            :class="isActive(link.route)
                                ? (user.role === 'pharmacien' ? 'bg-purple-600 text-white' : 'bg-emerald-600 text-white')
                                : 'bg-gray-100'">{{ link.icon }}</span>
                        {{ link.name }}
                    </Link>
                </div>

                <div class="border-t border-gray-100 py-2">
                    <Link :href="route('profile.edit')" class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                        <span class="w-7 h-7 bg-gray-100 rounded flex items-center justify-center text-xs font-bold text-gray-600">P</span>
                        Mon profil
                    </Link>
                    <Link :href="route('logout')" method="post" as="button" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-red-600 hover:bg-red-50 transition">
                        <span class="w-7 h-7 bg-red-100 rounded flex items-center justify-center text-xs font-bold">D</span>
                        Déconnexion
                    </Link>
                </div>
            </div>
        </nav>

        <header v-if="$slots.header" class="bg-white shadow-sm border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
                <slot name="header" />
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <!-- ===== Pied de page Boulal ===== -->
        <footer class="bg-white border-t border-gray-100 py-4 px-4">
            <div class="max-w-7xl mx-auto text-center">
                <p class="text-xs text-gray-500">
                    © 2026 Poste de Santé de Boulal · <span class="text-gray-400">Application développée par Salla NGOM</span>
                </p>
            </div>
        </footer>
    </div>
</template>
