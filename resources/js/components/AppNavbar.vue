<template>
    <header class="bg-white border-b border-slate-200 px-4 sm:px-6 py-3 flex items-center gap-4">
        <!-- Mobile menu button -->
        <button
            type="button"
            class="md:hidden p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors"
            aria-label="Toggle menu"
            @click="$emit('toggle-sidebar')"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <!-- Search -->
        <div class="flex-1 max-w-md">
            <form @submit.prevent="onSearchSubmit" class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    v-model="searchQuery"
                    type="search"
                    placeholder="Search products..."
                    class="w-full pl-10 pr-4 py-2 text-sm rounded-lg border border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-colors"
                />
            </form>
        </div>

        <!-- Spacer -->
        <div class="flex-1"></div>

        <!-- Notification button -->
        <button
            type="button"
            class="relative p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors"
            aria-label="Notifications"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v1m6 0H9"/>
            </svg>
            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-rose-500 rounded-full" aria-hidden="true"></span>
        </button>

        <!-- User menu -->
        <div class="relative" ref="menuRef">
            <button
                type="button"
                class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-100 transition-colors"
                aria-haspopup="true"
                :aria-expanded="menuOpen"
                @click="menuOpen = !menuOpen"
            >
                <div class="w-8 h-8 rounded-full bg-brand-600 flex items-center justify-center text-white text-xs font-semibold">
                    {{ auth.user?.name?.[0]?.toUpperCase() || '?' }}
                </div>
                <div class="hidden sm:block text-left">
                    <div class="text-sm font-medium text-slate-900 leading-tight">{{ auth.user?.name || 'User' }}</div>
                    <div class="text-xs text-slate-500 leading-tight capitalize">{{ auth.user?.role || '' }}</div>
                </div>
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <transition
                enter-active-class="transition duration-100 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-75 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="menuOpen"
                    class="absolute right-0 mt-2 w-48 origin-top-right rounded-lg bg-white shadow-lg ring-1 ring-black/5 py-1 z-50"
                    @click.stop
                >
                    <div class="px-3 py-2 border-b border-slate-100">
                        <div class="text-sm font-medium text-slate-900 truncate">{{ auth.user?.name }}</div>
                        <div class="text-xs text-slate-500 truncate">{{ auth.user?.email }}</div>
                    </div>
                    <button
                        type="button"
                        class="w-full flex items-center gap-2 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 transition-colors"
                        @click="handleLogout"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Logout
                    </button>
                </div>
            </transition>
        </div>
    </header>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter, useRoute } from 'vue-router';

defineEmits(['toggle-sidebar']);
defineProps({
    sidebarOpen: { type: Boolean, default: false },
});

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

const menuOpen = ref(false);
const menuRef = ref(null);
const searchQuery = ref('');

// Sync search query from URL ?search=...
onMounted(() => {
    if (route.query.search) searchQuery.value = String(route.query.search);
});

function handleLogout() {
    menuOpen.value = false;
    logout();
}

async function logout() {
    await auth.logout();
    router.push({ name: 'login' });
}

function handleClickOutside(event) {
    if (menuRef.value && !menuRef.value.contains(event.target)) {
        menuOpen.value = false;
    }
}

function onSearchSubmit() {
    const q = searchQuery.value.trim();
    if (!q) return;
    router.push({ name: 'products.index', query: { search: q } });
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>
