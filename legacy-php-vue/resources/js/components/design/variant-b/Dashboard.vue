<template>
    <div class="vb space-y-5">
        <!-- Header -->
        <div class="flex items-end justify-between">
            <div>
                <div class="text-xs text-zinc-500 mb-1">{{ date }}</div>
                <h1>Good morning, Rithy</h1>
                <p class="text-sm text-zinc-500 mt-1">Here's what's happening across your warehouses today.</p>
            </div>
            <div class="flex gap-2">
                <button class="h-9 px-3.5 text-xs font-semibold border border-zinc-200 bg-white rounded-md hover:bg-zinc-50">Today</button>
                <button class="h-9 px-3.5 text-xs font-semibold border border-zinc-200 bg-white rounded-md hover:bg-zinc-50">7d</button>
                <button class="h-9 px-3.5 text-xs font-semibold bg-zinc-950 text-white rounded-md">30 days</button>
            </div>
        </div>

        <!-- Stat cards -->
        <div class="grid grid-cols-4 gap-3">
            <div v-for="s in stats" :key="s.label" class="vb-card p-4">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-xs font-medium text-zinc-500">{{ s.label }}</p>
                        <p class="text-2xl font-bold text-zinc-950 mt-1.5 vb-mono">{{ s.value }}</p>
                        <p class="text-xs mt-1.5 flex items-center gap-1" :style="{ color: s.deltaColor }">
                            <svg v-if="s.up" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                            <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                            {{ s.delta }}
                        </p>
                    </div>
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center" :style="{ background: s.iconBg, color: s.iconColor }">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="s.iconPath"/>
                        </svg>
                    </div>
                </div>
                <!-- Mini sparkline -->
                <svg viewBox="0 0 100 30" class="w-full h-6 mt-3" preserveAspectRatio="none">
                    <polyline fill="none" :stroke="s.sparkColor" stroke-width="1.5" :points="s.spark"/>
                </svg>
            </div>
        </div>

        <!-- Two columns -->
        <div class="grid grid-cols-3 gap-3">
            <!-- Needs action -->
            <div class="col-span-2 vb-card">
                <div class="px-4 py-3 border-b border-zinc-100 flex items-center justify-between">
                    <div>
                        <h2>Needs your attention</h2>
                        <p class="text-xs text-zinc-500 mt-0.5">5 items · sorted by urgency</p>
                    </div>
                    <a href="#" class="text-xs font-semibold text-zinc-950 hover:text-sky-700">View all →</a>
                </div>
                <ul class="divide-y divide-zinc-100">
                    <li v-for="item in actions" :key="item.id" class="px-4 py-3 flex items-center gap-3 hover:bg-zinc-50">
                        <div class="w-8 h-8 rounded-md flex items-center justify-center" :style="{ background: item.bg, color: item.fg }">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-semibold text-zinc-950 truncate">{{ item.title }}</div>
                            <div class="text-xs text-zinc-500 truncate">{{ item.context }}</div>
                        </div>
                        <span class="vb-pill" :class="item.pillClass">{{ item.status }}</span>
                        <span class="vb-mono text-[11px] text-zinc-500 w-16 text-right">{{ item.ref }}</span>
                        <button class="text-xs font-semibold text-zinc-950 hover:text-sky-700">Open →</button>
                    </li>
                </ul>
            </div>

            <!-- Top movers -->
            <div class="vb-card">
                <div class="px-4 py-3 border-b border-zinc-100">
                    <h2>Top moving</h2>
                    <p class="text-xs text-zinc-500 mt-0.5">Last 30 days · units sold</p>
                </div>
                <ul class="divide-y divide-zinc-100">
                    <li v-for="(p, i) in topProducts" :key="p.sku" class="px-4 py-2.5 flex items-center gap-3 text-xs">
                        <span class="vb-mono text-zinc-400 w-4 text-right font-semibold">{{ i + 1 }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-zinc-950 truncate">{{ p.name }}</div>
                            <div class="vb-mono text-[10px] text-zinc-500">{{ p.sku }}</div>
                        </div>
                        <div class="vb-mono font-bold text-zinc-950">{{ p.units.toLocaleString() }}</div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Stock movement chart -->
        <div class="vb-card">
            <div class="px-4 py-3 border-b border-zinc-100 flex items-center justify-between">
                <div>
                    <h2>Stock movement</h2>
                    <p class="text-xs text-zinc-500 mt-0.5">Net flow across all warehouses · 14 days</p>
                </div>
                <div class="flex items-center gap-4 text-xs">
                    <span class="flex items-center gap-1.5 text-zinc-600"><span class="w-2 h-2 rounded-sm" :style="{ background: 'var(--vb-fresh)' }"></span>Inflow</span>
                    <span class="flex items-center gap-1.5 text-zinc-600"><span class="w-2 h-2 rounded-sm" :style="{ background: 'var(--vb-warning)' }"></span>Outflow</span>
                    <span class="flex items-center gap-1.5 text-zinc-600"><span class="w-2 h-2 rounded-sm" :style="{ background: 'var(--vb-info)' }"></span>Net</span>
                </div>
            </div>
            <div class="p-4">
                <svg viewBox="0 0 600 140" class="w-full h-32" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="vbGrad" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0" stop-color="var(--vb-fresh)" stop-opacity="0.25"/>
                            <stop offset="1" stop-color="var(--vb-fresh)" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <polyline fill="url(#vbGrad)" stroke="none" points="0,120 40,110 80,100 120,95 160,85 200,90 240,75 280,80 320,65 360,70 400,55 440,60 480,45 520,50 560,35 600,30 600,140 0,140"/>
                    <polyline fill="none" stroke="var(--vb-fresh)" stroke-width="2" points="0,120 40,110 80,100 120,95 160,85 200,90 240,75 280,80 320,65 360,70 400,55 440,60 480,45 520,50 560,35 600,30"/>
                    <polyline fill="none" stroke="var(--vb-warning)" stroke-width="2" stroke-dasharray="4,3" points="0,130 40,128 80,122 120,118 160,110 200,108 240,98 280,100 320,90 360,92 400,82 440,84 480,74 520,76 560,66 600,62"/>
                </svg>
                <div class="flex justify-between text-[10px] vb-mono text-zinc-400 mt-2">
                    <span>Jun 23</span><span>Jun 30</span><span>Jul 7</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const date = new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

const stats = [
    { label: "Today's sales",     value: '$1,284.50', delta: '+12.4% vs yesterday', up: true,  deltaColor: '#047857', iconBg: '#ecfdf5', iconColor: '#10b981', sparkColor: '#10b981', spark: '0,25 10,22 20,24 30,18 40,20 50,15 60,17 70,12 80,14 90,10 100,8', iconPath: 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z' },
    { label: 'Monthly revenue',   value: '$38,420',   delta: '+8.2% vs last month',  up: true,  deltaColor: '#047857', iconBg: '#f0f9ff', iconColor: '#0ea5e9', sparkColor: '#0ea5e9', spark: '0,20 10,18 20,22 30,16 40,14 50,17 60,12 70,10 80,13 90,8 100,6', iconPath: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
    { label: 'Expiring · 7d',     value: '12',        delta: '3 critical · 9 warning', up: false, deltaColor: '#b91c1c', iconBg: '#fef2f2', iconColor: '#ef4444', sparkColor: '#ef4444', spark: '0,18 10,16 20,20 30,15 40,17 50,14 60,18 70,12 80,14 90,10 100,8', iconPath: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
    { label: 'Pending approvals', value: '5',         delta: '2 awaiting you',        up: false, deltaColor: '#4f46e5', iconBg: '#eef2ff', iconColor: '#6366f1', sparkColor: '#6366f1', spark: '0,22 10,20 20,18 30,21 40,17 50,19 60,15 70,17 80,13 90,15 100,11', iconPath: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
];

const actions = [
    { id: 1, title: 'Approve PO-2024-0184', context: 'BrightFoods Co. · 12 line items · $4,820', status: 'High value', pillClass: 'vb-pill vb-pill-critical', ref: 'PO-0184', bg: '#fef2f2', fg: '#b91c1c', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
    { id: 2, title: 'Batch BTH-0921 expired', context: 'Soy sauce 750ml · 24 units in warehouse B', status: 'Critical', pillClass: 'vb-pill vb-pill-critical', ref: 'BTH-0921', bg: '#fef2f2', fg: '#b91c1c', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
    { id: 3, title: 'Low stock · Instant Noodles', context: 'Reorder point 50 · on hand 18 · PPH', status: 'Reorder', pillClass: 'vb-pill vb-pill-warning', ref: 'SKU-2287', bg: '#fffbeb', fg: '#b45309', icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' },
    { id: 4, title: 'Draft sale awaiting payment', context: 'A.Reach · 3 items · $284.00', status: 'Pending', pillClass: 'vb-pill vb-pill-info', ref: 'DSA-334', bg: '#eef2ff', fg: '#4f46e5', icon: 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z' },
    { id: 5, title: 'Transfer ready to dispatch', context: 'Phnom Penh → Siem Reap · 8 batches', status: 'In transit', pillClass: 'vb-pill vb-pill-info', ref: 'TRF-77', bg: '#eef2ff', fg: '#4f46e5', icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4' },
];

const topProducts = [
    { sku: 'SKU-1014', name: 'Coca-Cola 325ml Can', units: 1840 },
    { sku: 'SKU-2287', name: 'Instant Noodles · 30pk', units: 1245 },
    { sku: 'SKU-0421', name: 'Sunlight Soap 110g', units: 982 },
    { sku: 'SKU-3301', name: 'ABC Soy Sauce 750ml', units: 718 },
    { sku: 'SKU-5602', name: 'Pokka Green Tea 500ml', units: 564 },
];
</script>
