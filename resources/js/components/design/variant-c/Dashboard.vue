<template>
    <div class="vc space-y-5">
        <!-- Welcome banner with warmth -->
        <div class="vc-card p-5 flex items-center justify-between" style="background: linear-gradient(120deg, #fff7ed 0%, #fdfaf5 60%, white 100%); border-color: #fed7aa;">
            <div>
                <div class="text-xs font-semibold text-orange-700 uppercase tracking-wider">{{ greeting }},</div>
                <h1 class="mt-1">សួស្តី Rithy — Sok Rithy</h1>
                <p class="text-sm text-stone-600 mt-1">
                    You have <span class="font-bold text-red-700">3 expired batches</span> and
                    <span class="font-bold text-amber-700">5 pending approvals</span> to review today.
                </p>
            </div>
            <div class="flex gap-2 shrink-0">
                <button class="h-9 px-3.5 text-sm font-medium border border-stone-200 bg-white rounded-lg hover:bg-stone-50">Today</button>
                <button class="h-9 px-3.5 text-sm font-medium bg-stone-900 text-white rounded-lg">This month</button>
            </div>
        </div>

        <!-- Stat cards with stock-style status -->
        <div class="grid grid-cols-4 gap-3">
            <!-- Sales -->
            <div class="vc-card p-4">
                <div class="flex items-center justify-between">
                    <div class="text-xs font-medium text-stone-500">Today's sales</div>
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: #f0fdf4; color: #15803d;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-stone-950 mt-2 vc-mono">$1,284.50</div>
                <div class="text-xs text-green-700 mt-1 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    +12.4% from yesterday
                </div>
                <svg viewBox="0 0 100 30" class="w-full h-7 mt-3" preserveAspectRatio="none">
                    <polyline fill="none" stroke="#15803d" stroke-width="1.5" points="0,22 10,20 20,18 30,15 40,17 50,12 60,14 70,10 80,12 90,7 100,5"/>
                </svg>
            </div>

            <!-- Expiring — highlighted in red -->
            <div class="vc-card p-4" style="border-color: #fca5a5; background: linear-gradient(180deg, #fef2f2 0%, white 50%);">
                <div class="flex items-center justify-between">
                    <div class="text-xs font-bold text-red-700 uppercase tracking-wider">Expiring · 7d</div>
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: #fee2e2; color: #b91c1c;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-red-700 mt-2 vc-mono">12 batches</div>
                <div class="text-xs text-red-700 mt-1">3 expired · 9 within 7 days</div>
                <div class="mt-3 h-2 bg-stone-200 rounded-full overflow-hidden">
                    <div class="h-full bg-red-500" style="width: 25%"></div>
                </div>
            </div>

            <!-- Low stock — amber -->
            <div class="vc-card p-4" style="border-color: #fde68a; background: linear-gradient(180deg, #fefce8 0%, white 50%);">
                <div class="flex items-center justify-between">
                    <div class="text-xs font-bold text-amber-700 uppercase tracking-wider">Low stock</div>
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: #fef3c7; color: #b45309;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-amber-700 mt-2 vc-mono">3 SKUs</div>
                <div class="text-xs text-amber-700 mt-1">below reorder point</div>
                <div class="mt-3 h-2 bg-stone-200 rounded-full overflow-hidden">
                    <div class="h-full bg-amber-500" style="width: 60%"></div>
                </div>
            </div>

            <!-- Pending approvals — info -->
            <div class="vc-card p-4">
                <div class="flex items-center justify-between">
                    <div class="text-xs font-medium text-stone-500">Pending approvals</div>
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: #eff6ff; color: #1d4ed8;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-stone-950 mt-2 vc-mono">5</div>
                <div class="text-xs text-blue-700 mt-1">2 awaiting your review</div>
                <div class="mt-3 flex gap-1">
                    <span class="vc-pill vc-pill-info" style="font-size: 10px; padding: 2px 8px;">PO × 2</span>
                    <span class="vc-pill vc-pill-info" style="font-size: 10px; padding: 2px 8px;">Refund × 1</span>
                </div>
            </div>
        </div>

        <!-- Two columns -->
        <div class="grid grid-cols-3 gap-3">
            <!-- Action items -->
            <div class="col-span-2 vc-card">
                <div class="px-5 py-3.5 border-b border-stone-100 flex items-center justify-between">
                    <h2>Needs your attention</h2>
                    <a href="#" class="text-xs font-semibold text-orange-700 hover:text-orange-800">View all →</a>
                </div>
                <ul class="divide-y divide-stone-100">
                    <li v-for="item in actions" :key="item.id" class="px-5 py-3.5 flex items-center gap-3 hover:bg-stone-50">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center" :style="{ background: item.bg, color: item.fg }">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-semibold text-stone-900 truncate">{{ item.title }}</div>
                            <div class="text-xs text-stone-500 truncate">{{ item.context }}</div>
                        </div>
                        <span class="vc-pill" :class="item.pillClass">{{ item.status }}</span>
                        <span class="vc-mono text-xs text-stone-500 w-20 text-right">{{ item.ref }}</span>
                        <button class="text-sm font-semibold text-orange-700 hover:text-orange-800 whitespace-nowrap">Review →</button>
                    </li>
                </ul>
            </div>

            <!-- Top movers with imagery accent -->
            <div class="vc-card">
                <div class="px-5 py-3.5 border-b border-stone-100">
                    <h2>Top moving</h2>
                    <p class="text-xs text-stone-500 mt-0.5">Last 30 days</p>
                </div>
                <ul class="divide-y divide-stone-100">
                    <li v-for="(p, i) in topProducts" :key="p.sku" class="px-5 py-2.5 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-md flex items-center justify-center text-xs font-bold" :style="{ background: p.bg, color: p.fg }">
                            {{ p.initial }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-semibold text-stone-900 truncate">{{ p.name }}</div>
                            <div class="vc-mono text-[10px] text-stone-500">{{ p.sku }}</div>
                        </div>
                        <div class="text-right">
                            <div class="vc-mono text-sm font-bold text-stone-950">{{ p.units.toLocaleString() }}</div>
                            <div class="text-[10px] text-stone-500">units</div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Stock movement -->
        <div class="vc-card">
            <div class="px-5 py-3.5 border-b border-stone-100 flex items-center justify-between">
                <div>
                    <h2>Stock movement</h2>
                    <p class="text-xs text-stone-500 mt-0.5">Net flow across all warehouses · 14 days</p>
                </div>
                <div class="flex items-center gap-4 text-xs text-stone-600">
                    <span class="flex items-center gap-1.5"><span class="vc-dot vc-dot-fresh"></span>Inflow</span>
                    <span class="flex items-center gap-1.5"><span class="vc-dot vc-dot-warning"></span>Outflow</span>
                    <span class="flex items-center gap-1.5"><span class="vc-dot vc-dot-info"></span>Net</span>
                </div>
            </div>
            <div class="p-5">
                <svg viewBox="0 0 600 140" class="w-full h-32" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="vcGrad" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0" stop-color="#15803d" stop-opacity="0.2"/>
                            <stop offset="1" stop-color="#15803d" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <polyline fill="url(#vcGrad)" stroke="none" points="0,120 40,110 80,100 120,95 160,85 200,90 240,75 280,80 320,65 360,70 400,55 440,60 480,45 520,50 560,35 600,30 600,140 0,140"/>
                    <polyline fill="none" stroke="#15803d" stroke-width="2.5" stroke-linecap="round" points="0,120 40,110 80,100 120,95 160,85 200,90 240,75 280,80 320,65 360,70 400,55 440,60 480,45 520,50 560,35 600,30"/>
                    <polyline fill="none" stroke="#ca8a04" stroke-width="2" stroke-dasharray="5,3" stroke-linecap="round" points="0,128 40,125 80,120 120,116 160,108 200,106 240,98 280,100 320,90 360,92 400,82 440,84 480,74 520,76 560,66 600,62"/>
                </svg>
                <div class="flex justify-between text-[10px] vc-mono text-stone-400 mt-2">
                    <span>Jun 23</span><span>Jun 30</span><span>Jul 7</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const greeting = computed(() => {
    const h = new Date().getHours();
    if (h < 12) return 'Good morning';
    if (h < 17) return 'Good afternoon';
    return 'Good evening';
});

const actions = [
    { id: 1, title: 'Approve PO-2024-0184', context: 'BrightFoods Co. · 12 items · $4,820', status: 'High value', pillClass: 'vc-pill vc-pill-critical', ref: 'PO-0184', bg: '#fef2f2', fg: '#b91c1c', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
    { id: 2, title: '3 batches expired in warehouse B', context: 'Soy sauce 750ml · Tofu · Noodles', status: 'Critical', pillClass: 'vc-pill vc-pill-critical', ref: 'BTH-0921', bg: '#fef2f2', fg: '#b91c1c', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
    { id: 3, title: 'Reorder: Instant Noodles 30pk', context: 'On hand 18 / threshold 50 · PPH', status: 'Reorder', pillClass: 'vc-pill vc-pill-warning', ref: 'SKU-2287', bg: '#fefce8', fg: '#854d0e', icon: 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z' },
    { id: 4, title: 'Draft sale awaiting payment', context: 'A.Reach · 3 items · $284.00', status: 'Pending', pillClass: 'vc-pill vc-pill-info', ref: 'DSA-334', bg: '#eff6ff', fg: '#1e40af', icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z' },
    { id: 5, title: 'Transfer ready to dispatch', context: 'Phnom Penh → Siem Reap · 8 batches', status: 'In transit', pillClass: 'vc-pill vc-pill-info', ref: 'TRF-77', bg: '#eff6ff', fg: '#1e40af', icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4' },
];

const topProducts = [
    { sku: 'SKU-1014', name: 'Coca-Cola 325ml Can', units: 1840, initial: 'C', bg: '#fee2e2', fg: '#b91c1c' },
    { sku: 'SKU-2287', name: 'Instant Noodles · 30pk', units: 1245, initial: 'I', bg: '#fef3c7', fg: '#b45309' },
    { sku: 'SKU-0421', name: 'Sunlight Soap 110g', units: 982, initial: 'S', bg: '#dbeafe', fg: '#1e40af' },
    { sku: 'SKU-3301', name: 'ABC Soy Sauce 750ml', units: 718, initial: 'A', bg: '#dcfce7', fg: '#166534' },
    { sku: 'SKU-5602', name: 'Pokka Green Tea 500ml', units: 564, initial: 'P', bg: '#f0fdf4', fg: '#15803d' },
];
</script>
