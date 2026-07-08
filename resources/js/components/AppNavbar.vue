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
                    :placeholder="$t('common.search_products')"
                    class="w-full pl-9 pr-16 py-1.5 text-[13px] rounded-md border border-slate-300 bg-white text-slate-900 placeholder-slate-400 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500/30 transition-colors font-mono"
                />
                <span class="absolute right-2.5 top-1/2 -translate-y-1/2 hidden sm:flex items-center gap-0.5 text-[10px] font-mono text-slate-400 border border-slate-200 bg-slate-50 px-1.5 py-0.5 rounded pointer-events-none">
                    <span>⌘</span><span>K</span>
                </span>
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
            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-status-critical rounded-full ring-2 ring-white" aria-hidden="true"></span>
        </button>

        <!-- Language switcher -->
        <div class="relative" ref="langMenuRef">
            <button
                type="button"
                class="flex items-center gap-1 px-2 py-1 text-[13px] text-slate-700 hover:bg-slate-100 rounded-md transition-colors"
                aria-haspopup="true"
                :aria-expanded="langMenuOpen"
                :aria-label="$t('common.language')"
                @click="langMenuOpen = !langMenuOpen"
            >
                <span aria-hidden="true">🌐</span>
                <span class="hidden sm:inline font-medium">{{ settings.locale === 'km' ? 'ខ្មែរ' : 'EN' }}</span>
                <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
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
                    v-if="langMenuOpen"
                    class="absolute right-0 mt-2 w-36 origin-top-right rounded-lg bg-white shadow-lg ring-1 ring-black/5 py-1 z-50"
                    @click.stop
                >
                    <button
                        type="button"
                        class="w-full flex items-center justify-between px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                        @click="selectLocale('en')"
                    >
                        <span>English</span>
                        <span v-if="settings.locale === 'en'" class="text-brand-600">✓</span>
                    </button>
                    <button
                        type="button"
                        class="w-full flex items-center justify-between px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 font-km"
                        @click="selectLocale('km')"
                    >
                        <span>ភាសាខ្មែរ</span>
                        <span v-if="settings.locale === 'km'" class="text-brand-600">✓</span>
                    </button>
                </div>
            </transition>
        </div>

        <!-- Currency switcher -->
        <div class="relative" ref="curMenuRef">
            <button
                type="button"
                class="flex items-center gap-1 px-2 py-1 text-[13px] text-slate-700 hover:bg-slate-100 rounded-md transition-colors"
                aria-haspopup="true"
                :aria-expanded="curMenuOpen"
                :aria-label="$t('common.currency')"
                @click="curMenuOpen = !curMenuOpen"
            >
                <span class="font-medium" aria-hidden="true">{{ settings.displayCurrency === 'KHR' ? '៛' : '$' }}</span>
                <span class="hidden sm:inline font-medium">{{ settings.displayCurrency }}</span>
                <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
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
                    v-if="curMenuOpen"
                    class="absolute right-0 mt-2 w-32 origin-top-right rounded-lg bg-white shadow-lg ring-1 ring-black/5 py-1 z-50"
                    @click.stop
                >
                    <button
                        type="button"
                        class="w-full flex items-center justify-between px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                        @click="selectCurrency('USD')"
                    >
                        <span>$ USD</span>
                        <span v-if="settings.displayCurrency === 'USD'" class="text-brand-600">✓</span>
                    </button>
                    <button
                        type="button"
                        class="w-full flex items-center justify-between px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                        @click="selectCurrency('KHR')"
                    >
                        <span>៛ KHR</span>
                        <span v-if="settings.displayCurrency === 'KHR'" class="text-brand-600">✓</span>
                    </button>
                </div>
            </transition>
        </div>

        <!-- User menu -->
        <div class="relative" ref="menuRef">
            <button
                type="button"
                class="flex items-center gap-2 px-2 py-1 rounded-md hover:bg-slate-100 transition-colors"
                aria-haspopup="true"
                :aria-expanded="menuOpen"
                @click="menuOpen = !menuOpen"
            >
                <div class="relative w-8 h-8 rounded-full bg-brand-600 flex items-center justify-center text-white text-xs font-semibold">
                    {{ auth.user?.name?.[0]?.toUpperCase() || '?' }}
                    <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-status-fresh border-2 border-white" aria-label="Online"></span>
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
                        {{ $t('common.logout') }}
                    </button>
                </div>
            </transition>
        </div>
    </header>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useSettingsStore } from '@/stores/settings';
import { setI18nLocale } from '@/i18n';
import { useRouter, useRoute } from 'vue-router';

defineEmits(['toggle-sidebar']);
defineProps({
    sidebarOpen: { type: Boolean, default: false },
});

const auth = useAuthStore();
const settings = useSettingsStore();
const router = useRouter();
const route = useRoute();

const menuOpen = ref(false);
const menuRef = ref(null);
const langMenuOpen = ref(false);
const langMenuRef = ref(null);
const curMenuOpen = ref(false);
const curMenuRef = ref(null);
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

function selectLocale(loc) {
    settings.setLocale(loc);
    setI18nLocale(loc);
    langMenuOpen.value = false;
}

function selectCurrency(c) {
    settings.setCurrency(c);
    curMenuOpen.value = false;
}

function handleClickOutside(event) {
    if (menuRef.value && !menuRef.value.contains(event.target)) {
        menuOpen.value = false;
    }
    if (langMenuRef.value && !langMenuRef.value.contains(event.target)) {
        langMenuOpen.value = false;
    }
    if (curMenuRef.value && !curMenuRef.value.contains(event.target)) {
        curMenuOpen.value = false;
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
