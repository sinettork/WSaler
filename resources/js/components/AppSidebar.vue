<template>
    <aside
        class="flex flex-col h-full bg-sidebar-bg text-sidebar-fg w-60 shrink-0 border-r border-sidebar-border"
    >
        <div class="flex items-center gap-3 px-5 py-5 border-b border-sidebar-border">
            <div class="w-9 h-9 rounded-md flex items-center justify-center font-bold text-base shrink-0 bg-brand-600 text-white shadow-sm shadow-brand-600/40">
                W
            </div>
            <div class="min-w-0">
                <div class="text-sm font-semibold text-white leading-tight truncate">WSaler</div>
                <div class="text-xs text-sidebar-muted leading-tight truncate">Warehouse Ops</div>
            </div>
        </div>

        <nav class="flex-1 min-h-0 overflow-y-auto px-3 py-4 space-y-1 scrollbar-hide" aria-label="Primary">
            <router-link
                v-if="auth.hasRole(['admin','manager','cashier','warehouse'])"
                to="/pos"
                class="flex items-center gap-3 px-3 py-2 rounded-md text-[13px] font-semibold transition-colors"
                :class="isActive('/pos')
                    ? 'bg-brand-600 text-white hover:bg-brand-700'
                    : 'bg-brand-600/10 text-brand-600 hover:bg-brand-600 hover:text-white'"
            >
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span>{{ $t('nav.pos') }}</span>
            </router-link>

            <router-link
                to="/dashboard"
                class="flex items-center gap-3 pl-3 pr-3 py-2 rounded-md text-[13px] transition-colors border-l-2"
                :class="isActive('/dashboard')
                    ? 'bg-sidebar-active text-white font-medium border-sidebar-accent'
                    : 'text-sidebar-fg hover:bg-sidebar-active hover:text-white border-transparent'"
            >
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7m-9 2v8a2 2 0 002 2h2m-6-10h6m0 0v8a2 2 0 002 2h2"/>
                </svg>
                <span>{{ $t('nav.dashboard') }}</span>
            </router-link>

            <template v-for="section in sections" :key="section.id">
                <div v-if="hasAnyVisibleItem(section)" class="pt-3">
                    <button
                        type="button"
                        class="w-full flex items-center justify-between px-3 py-1.5 text-sm font-medium text-sidebar-muted hover:text-white transition-colors"
                        :aria-expanded="isExpanded(section.id)"
                        :aria-controls="`sidebar-section-${section.id}`"
                        @click="toggleSection(section.id)"
                    >
                        <span class="text-sm font-medium">{{ $t(section.label) }}</span>
                        <svg
                            class="w-3 h-3 transition-transform shrink-0"
                            :class="{ 'rotate-90': isExpanded(section.id) }"
                            fill="none"
                            stroke="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>

                    <div
                        :id="`sidebar-section-${section.id}`"
                        class="overflow-hidden transition-all duration-200 ease-out"
                        :style="{
                            maxHeight: isExpanded(section.id) ? '500px' : '0px',
                            opacity: isExpanded(section.id) ? '1' : '0',
                        }"
                    >
                        <div class="mt-1 space-y-0.5">
                            <router-link
                                v-for="item in visibleItems(section)"
                                :key="item.path"
                                :to="item.path"
                                class="flex items-center gap-3 pl-9 pr-3 py-1.5 rounded-md text-[13px] transition-colors border-l-2"
                                :class="isActive(item.path)
                                    ? 'bg-sidebar-active text-white font-medium border-sidebar-accent'
                                    : 'text-sidebar-fg hover:bg-sidebar-active hover:text-white border-transparent'"
                            >
                                <svg class="w-3.5 h-3.5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                                </svg>
                                <span class="truncate">{{ $t(item.label) }}</span>
                            </router-link>
                        </div>
                    </div>
                </div>
            </template>
        </nav>

        <div class="px-3 py-3 border-t border-sidebar-border">
            <div class="flex items-center gap-3 px-3 py-2">
                <div class="relative w-8 h-8 rounded-full bg-brand-600 flex items-center justify-center text-white text-xs font-semibold shrink-0">
                    {{ auth.user?.name?.[0]?.toUpperCase() || '?' }}
                    <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-status-fresh border-2 border-sidebar-bg" aria-label="Online"></span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-[13px] font-medium text-white truncate">{{ auth.user?.name || 'User' }}</div>
                    <div class="text-xs text-sidebar-muted truncate">{{ auth.user?.role || '' }}</div>
                </div>
            </div>
        </div>
    </aside>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRoute } from 'vue-router';

const STORAGE_KEY = 'sidebarExpandedSections';

const auth = useAuthStore();
const route = useRoute();

const ICONS = {
    users: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
    tag: 'M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.99 1.99 0 013 12V7a4 4 0 014-4z',
    cube: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    truck: 'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0',
    inbox: 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4',
    scale: 'M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3',
    usersGroup: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
    ruler: 'M3 6h18M3 12h18M3 18h18',
    building: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
    receipt: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
    user: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    gear: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
};

const sections = [
    {
        id: 'sales',
        label: 'nav.sales',
        items: [
            { path: '/sales', label: 'nav.sales', icon: ICONS.receipt, roles: ['admin', 'manager', 'cashier', 'warehouse'] },
        ],
    },
    {
        id: 'admin',
        label: 'nav.admin',
        items: [
            { path: '/admin/users', label: 'nav.users', icon: ICONS.users, roles: ['admin'] },
        ],
    },
    {
        id: 'master',
        label: 'nav.master',
        items: [
            { path: '/master/categories', label: 'nav.categories', icon: ICONS.tag, roles: null },
            { path: '/master/brands', label: 'nav.brands', icon: ICONS.cube, roles: null },
            { path: '/master/suppliers', label: 'nav.suppliers', icon: ICONS.truck, roles: ['admin', 'manager', 'purchasing'] },
            { path: '/master/customers', label: 'nav.customers', icon: ICONS.usersGroup, roles: null },
        ],
    },
    {
        id: 'inventory',
        label: 'nav.products',
        items: [
            { path: '/products', label: 'nav.products', icon: ICONS.cube, roles: null },
            { path: '/batches', label: 'nav.batches', icon: ICONS.inbox, roles: null },
            { path: '/inventory/operations', label: 'nav.inventoryOperations', icon: ICONS.receipt, roles: null },
            { path: '/units', label: 'nav.units', icon: ICONS.scale, roles: ['admin', 'manager'] },
            { path: '/warehouses', label: 'nav.warehouses', icon: ICONS.building, roles: ['admin', 'manager'] },
        ],
    },
    {
        id: 'settings',
        label: 'nav.settings',
        items: [
            { path: '/settings', label: 'nav.userSettings', icon: ICONS.user, roles: null },
            { path: '/settings/app', label: 'nav.appSettings', icon: ICONS.gear, roles: ['admin', 'manager'] },
        ],
    },
];

const expanded = ref({});

function isActive(path) {
    return route.path === path || route.path.startsWith(`${path}/`);
}

function isExpanded(id) {
    return Boolean(expanded.value[id]);
}

function toggleSection(id) {
    expanded.value[id] = !expanded.value[id];
    saveExpanded();
}

function isSectionActive(section) {
    return section.items.some((item) => isActive(item.path));
}

function hasAnyVisibleItem(section) {
    return visibleItems(section).length > 0;
}

function visibleItems(section) {
    return section.items.filter((item) => !item.roles || auth.hasRole(item.roles));
}

function saveExpanded() {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(expanded.value));
    } catch (e) {
        // storage unavailable, ignore
    }
}

function loadExpanded() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (raw) {
            const parsed = JSON.parse(raw);
            if (parsed && typeof parsed === 'object') {
                expanded.value = parsed;
            }
        }
    } catch (e) {
        // ignore parse errors
    }

    // Auto-expand sections that contain the current active route
    for (const section of sections) {
        if (isSectionActive(section)) {
            expanded.value[section.id] = true;
        }
    }
}

// Watch for route changes and auto-expand relevant sections
watch(() => route.path, () => {
    for (const section of sections) {
        if (isSectionActive(section)) {
            expanded.value[section.id] = true;
            saveExpanded();
        }
    }
});

onMounted(loadExpanded);
</script>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
