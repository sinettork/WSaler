<template>
    <div class="min-h-screen bg-zinc-100">
        <!-- Sticky control bar -->
        <div class="sticky top-0 z-50 bg-zinc-950 text-zinc-100 shadow-xl">
            <div class="max-w-[1400px] mx-auto px-6 py-3">
                <div class="flex items-center justify-between gap-4 mb-3">
                    <div class="flex items-center gap-3">
                        <router-link to="/dashboard"
                                    class="text-xs font-semibold text-zinc-400 hover:text-white flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Back to app
                        </router-link>
                        <span class="text-zinc-700">·</span>
                        <div>
                            <div class="text-[10px] uppercase tracking-widest text-zinc-500">Design exploration</div>
                            <div class="text-sm font-bold text-white">WSaler UI direction — 3 variants</div>
                        </div>
                    </div>
                    <div class="text-[10px] text-zinc-500 hidden md:block">
                        Static mockups · Pick a direction to apply globally
                    </div>
                </div>

                <!-- Variant tabs -->
                <div class="flex gap-1 mb-2">
                    <button v-for="v in variants" :key="v.id"
                            @click="variant = v.id"
                            class="flex-1 max-w-[280px] px-4 py-2 rounded-md text-xs font-semibold flex items-center justify-between transition-all"
                            :class="variant === v.id ? 'bg-white text-zinc-950' : 'bg-zinc-900 text-zinc-300 hover:bg-zinc-800'">
                        <span class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-sm" :style="{ background: v.swatch }"></span>
                            {{ v.label }}
                        </span>
                        <span class="text-[10px] opacity-70">{{ v.tag }}</span>
                    </button>
                </div>

                <!-- Surface tabs -->
                <div class="flex gap-1">
                    <button v-for="s in surfaces" :key="s.id"
                            @click="surface = s.id"
                            class="px-3 py-1.5 rounded-md text-xs font-medium transition-all"
                            :class="surface === s.id ? 'bg-amber-500 text-zinc-950' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700 hover:text-zinc-200'">
                        {{ s.label }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Variant description strip -->
        <div class="bg-white border-b border-zinc-200">
            <div class="max-w-[1400px] mx-auto px-6 py-3 flex items-center gap-4">
                <div class="text-[10px] uppercase tracking-widest text-zinc-500 font-semibold">
                    {{ currentVariant.label }}
                </div>
                <div class="text-sm text-zinc-700 flex-1">
                    {{ currentVariant.description }}
                </div>
                <div class="hidden md:flex items-center gap-3 text-[10px] text-zinc-500">
                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-sm bg-emerald-500"></span>Fresh</span>
                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-sm bg-amber-500"></span>Warning</span>
                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-sm bg-rose-500"></span>Critical</span>
                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-sm bg-sky-500"></span>Info</span>
                </div>
            </div>
        </div>

        <!-- Preview canvas -->
        <div class="max-w-[1400px] mx-auto p-6">
            <div class="bg-white border border-zinc-300 shadow-2xl shadow-zinc-950/10 overflow-hidden">
                <!-- Login + Dashboard: full-bleed surfaces, no chrome wrapper -->
                <component v-if="surface === 'login'" :is="currentVariant.Login" />
                <component v-else-if="surface === 'dashboard'" :is="currentVariant.Dashboard" />
                <!-- Products list + sidebar/chrome: wrap in the chrome with main content as the surface -->
                <component v-else-if="surface === 'products'" :is="currentVariant.Chrome">
                    <component :is="currentVariant.ProductBatchList" />
                </component>
                <component v-else :is="currentVariant.Chrome">
                    <div class="p-12 text-center text-stone-400 text-sm">
                        Sidebar + top bar preview. Switch to <strong>Products</strong> or <strong>Dashboard</strong> to see them inside this chrome.
                    </div>
                </component>
            </div>

            <!-- Spec callouts below the preview -->
            <div class="mt-6 grid grid-cols-3 gap-4 text-xs">
                <div class="bg-white border border-zinc-200 p-4">
                    <div class="text-[10px] uppercase tracking-widest text-zinc-500 font-semibold mb-2">Color palette</div>
                    <div class="flex flex-wrap gap-1.5">
                        <span v-for="c in currentVariant.colors" :key="c.label"
                              class="flex items-center gap-1.5 px-2 py-1 rounded bg-zinc-50 border border-zinc-200">
                            <span class="w-3 h-3 rounded-sm" :style="{ background: c.value }"></span>
                            <span class="font-mono text-[10px] text-zinc-700">{{ c.label }}</span>
                        </span>
                    </div>
                </div>
                <div class="bg-white border border-zinc-200 p-4">
                    <div class="text-[10px] uppercase tracking-widest text-zinc-500 font-semibold mb-2">Typography</div>
                    <div class="space-y-1">
                        <div class="font-bold text-zinc-950">{{ currentVariant.typeface }}</div>
                        <div class="font-mono text-[11px] text-zinc-500">Mono: {{ currentVariant.mono }}</div>
                        <div class="text-[11px] text-zinc-500">Base: 14px · density: {{ currentVariant.density }}</div>
                    </div>
                </div>
                <div class="bg-white border border-zinc-200 p-4">
                    <div class="text-[10px] uppercase tracking-widest text-zinc-500 font-semibold mb-2">Signature moves</div>
                    <ul class="space-y-1 text-zinc-700">
                        <li v-for="m in currentVariant.moves" :key="m" class="flex gap-1.5">
                            <span class="text-amber-500">→</span>
                            <span>{{ m }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, markRaw } from 'vue';

// Import tokens CSS (Vite bundles these into the page)
import '../components/design/variant-a/tokens.css';
import '../components/design/variant-b/tokens.css';
import '../components/design/variant-c/tokens.css';

// Import variant surfaces (markRaw — purely presentational, no reactivity needed)
import ALogin from '../components/design/variant-a/Login.vue';
import ADashboard from '../components/design/variant-a/Dashboard.vue';
import AChrome from '../components/design/variant-a/Chrome.vue';
import AProductList from '../components/design/variant-a/ProductBatchList.vue';

import BLogin from '../components/design/variant-b/Login.vue';
import BDashboard from '../components/design/variant-b/Dashboard.vue';
import BChrome from '../components/design/variant-b/Chrome.vue';
import BProductList from '../components/design/variant-b/ProductBatchList.vue';

import CLogin from '../components/design/variant-c/Login.vue';
import CDashboard from '../components/design/variant-c/Dashboard.vue';
import CChrome from '../components/design/variant-c/Chrome.vue';
import CProductList from '../components/design/variant-c/ProductBatchList.vue';

const variant = ref('a');
const surface = ref('dashboard');

const variants = {
    a: {
        id: 'a',
        label: 'A — Warehouse Operational',
        tag: 'Dense · Industrial',
        swatch: '#0c4a6e',
        description: 'Concrete-grey base with functional safety colors. Monospace codes, dense rows, monochrome chrome. Built for power users living in the app 8h/day.',
        typeface: 'Google Sans / Inter',
        mono: 'JetBrains Mono',
        density: '40px row · 14px base',
        colors: [
            { label: 'Brand', value: '#0c4a6e' },
            { label: 'Fresh', value: '#15803d' },
            { label: 'Warning', value: '#a16207' },
            { label: 'Critical', value: '#b91c1c' },
            { label: 'Info', value: '#1d4ed8' },
            { label: 'Sidebar', value: '#1c1917' },
        ],
        moves: [
            'Status encoded in color, not decoration',
            'Mono for SKU/batch/qty — readable when scanning',
            'Single-row KPI strip instead of 4 separate cards',
            'Sidebar shows counts (12 expiring) inline',
        ],
        Login: markRaw(ALogin),
        Dashboard: markRaw(ADashboard),
        Chrome: markRaw(AChrome),
        ProductBatchList: markRaw(AProductList),
    },
    b: {
        id: 'b',
        label: 'B — Modern Distribution',
        tag: 'Sharp · Refined',
        swatch: '#09090b',
        description: 'Charcoal base, one bold accent, sharp typography hierarchy. Linear/Notion clarity with warehouse cues. Most "premium SaaS" of the three.',
        typeface: 'Google Sans / Inter',
        mono: 'JetBrains Mono',
        density: '48px row · 14px base',
        colors: [
            { label: 'Brand', value: '#0ea5e9' },
            { label: 'Fresh', value: '#10b981' },
            { label: 'Warning', value: '#f59e0b' },
            { label: 'Critical', value: '#ef4444' },
            { label: 'Info', value: '#6366f1' },
            { label: 'Sidebar', value: '#09090b' },
        ],
        moves: [
            'Active nav item gets white card-on-black "pop"',
            'Stat cards with embedded sparklines',
            'Tabs (not filters) for status segmentation',
            'Brand color = data viz, not chrome',
        ],
        Login: markRaw(BLogin),
        Dashboard: markRaw(BDashboard),
        Chrome: markRaw(BChrome),
        ProductBatchList: markRaw(BProductList),
    },
    c: {
        id: 'c',
        label: 'C — Market-Forward',
        tag: 'Warm · Distinctive',
        swatch: '#c2410c',
        description: 'Cream + warm dark brown sidebar + orange accent. Subtle Southeast Asian sensibility, memorable without being kitsch. Functional color preserved for stock state.',
        typeface: 'Google Sans / Noto Sans Khmer',
        mono: 'JetBrains Mono',
        density: '52px row · 14px base',
        colors: [
            { label: 'Brand', value: '#ea580c' },
            { label: 'Fresh', value: '#15803d' },
            { label: 'Warning', value: '#ca8a04' },
            { label: 'Critical', value: '#b91c1c' },
            { label: 'Info', value: '#1d4ed8' },
            { label: 'Sidebar', value: '#2b1f15' },
        ],
        moves: [
            'Sidebar doubles as identity (warm brown vs generic black)',
            'Per-row color swatches make catalog scannable',
            'Welcome banner speaks in Khmer (real bilingual)',
            'Status cards get tinted backgrounds (red-50, amber-50)',
        ],
        Login: markRaw(CLogin),
        Dashboard: markRaw(CDashboard),
        Chrome: markRaw(CChrome),
        ProductBatchList: markRaw(CProductList),
    },
};

const surfaces = [
    { id: 'login',     label: 'Login' },
    { id: 'dashboard', label: 'Dashboard' },
    { id: 'products',  label: 'Products / Batches list' },
    { id: 'chrome',    label: 'Sidebar + app chrome' },
];

const currentVariant = computed(() => variants[variant.value]);
</script>
