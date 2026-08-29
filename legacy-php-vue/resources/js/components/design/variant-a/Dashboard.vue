<template>
    <div class="va space-y-4">
        <!-- Header strip -->
        <div class="flex items-baseline justify-between">
            <div>
                <div class="text-[10px] uppercase tracking-widest text-stone-500">Operations dashboard</div>
                <h1 class="text-xl font-bold text-stone-900">
                    Good morning, Rithy
                </h1>
                <div class="text-xs text-stone-500 mt-0.5">{{ date }} · Phnom Penh warehouse</div>
            </div>
            <div class="flex gap-1.5">
                <button class="h-8 px-3 text-xs font-semibold border border-stone-300 bg-white hover:bg-stone-100">Today</button>
                <button class="h-8 px-3 text-xs font-semibold border border-stone-300 bg-white hover:bg-stone-100">7d</button>
                <button class="h-8 px-3 text-xs font-semibold bg-stone-800 text-white">30d</button>
                <button class="h-8 px-3 text-xs font-semibold border border-stone-300 bg-white hover:bg-stone-100">Custom</button>
            </div>
        </div>

        <!-- KPI strip — single horizontal row, dense -->
        <div class="grid grid-cols-6 border border-stone-300 bg-white">
            <div class="p-3 border-r border-stone-200">
                <div class="text-[10px] uppercase tracking-wider text-stone-500">Sales today</div>
                <div class="text-xl font-bold text-stone-900 mt-1 va-mono">$1,284.50</div>
                <div class="text-[11px] text-green-700 va-mono mt-0.5">+12.4% vs yesterday</div>
            </div>
            <div class="p-3 border-r border-stone-200">
                <div class="text-[10px] uppercase tracking-wider text-stone-500">Orders</div>
                <div class="text-xl font-bold text-stone-900 mt-1 va-mono">23</div>
                <div class="text-[11px] text-stone-500 va-mono mt-0.5">17 paid · 6 draft</div>
            </div>
            <div class="p-3 border-r border-stone-200 bg-red-50">
                <div class="text-[10px] uppercase tracking-wider text-red-700 font-semibold">Expiring 7d</div>
                <div class="text-xl font-bold text-red-700 mt-1 va-mono">12</div>
                <div class="text-[11px] text-red-700 va-mono mt-0.5">3 critical · 9 warning</div>
            </div>
            <div class="p-3 border-r border-stone-200 bg-amber-50">
                <div class="text-[10px] uppercase tracking-wider text-amber-700 font-semibold">Low stock</div>
                <div class="text-xl font-bold text-amber-700 mt-1 va-mono">3</div>
                <div class="text-[11px] text-amber-700 va-mono mt-0.5">below threshold</div>
            </div>
            <div class="p-3 border-r border-stone-200">
                <div class="text-[10px] uppercase tracking-wider text-stone-500">Pending receipts</div>
                <div class="text-xl font-bold text-stone-900 mt-1 va-mono">7</div>
                <div class="text-[11px] text-blue-700 va-mono mt-0.5">3 suppliers due</div>
            </div>
            <div class="p-3">
                <div class="text-[10px] uppercase tracking-wider text-stone-500">Approvals</div>
                <div class="text-xl font-bold text-stone-900 mt-1 va-mono">5</div>
                <div class="text-[11px] text-blue-700 va-mono mt-0.5">awaiting your review</div>
            </div>
        </div>

        <!-- Two-column: needs action + recent activity -->
        <div class="grid grid-cols-5 gap-3">
            <!-- Needs action -->
            <div class="col-span-3 border border-stone-300 bg-white">
                <div class="px-3 py-2 border-b border-stone-200 bg-stone-100 flex items-center justify-between">
                    <h2 class="text-[11px] font-semibold text-stone-700 uppercase tracking-wider">Needs your action</h2>
                    <span class="va-badge va-badge-critical">5 pending</span>
                </div>
                <ul class="divide-y divide-stone-200">
                    <li v-for="item in actionItems" :key="item.id" class="px-3 py-2 flex items-center gap-3 hover:bg-stone-50">
                        <span class="va-dot" :class="dotClass(item.severity)"></span>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-semibold text-stone-900 truncate">{{ item.title }}</div>
                            <div class="text-[11px] text-stone-500 truncate">{{ item.context }}</div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="va-mono text-[11px] text-stone-700">{{ item.ref }}</div>
                            <div class="text-[10px] text-stone-500">{{ item.age }}</div>
                        </div>
                        <button class="text-[11px] font-semibold text-sky-900 hover:underline">Review →</button>
                    </li>
                </ul>
            </div>

            <!-- Top sellers -->
            <div class="col-span-2 border border-stone-300 bg-white">
                <div class="px-3 py-2 border-b border-stone-200 bg-stone-100">
                    <h2 class="text-[11px] font-semibold text-stone-700 uppercase tracking-wider">Top moving · 30d</h2>
                </div>
                <ul class="divide-y divide-stone-200">
                    <li v-for="(p, i) in topProducts" :key="p.sku" class="px-3 py-2 flex items-center gap-2 text-xs">
                        <span class="va-mono text-stone-400 w-5 text-right">{{ i + 1 }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-stone-900 truncate">{{ p.name }}</div>
                            <div class="va-mono text-[10px] text-stone-500">{{ p.sku }}</div>
                        </div>
                        <div class="text-right">
                            <div class="va-mono font-bold text-stone-900">{{ p.units }}</div>
                            <div class="text-[10px] text-stone-500">units</div>
                        </div>
                        <div class="w-16 h-1.5 bg-stone-200">
                            <div class="h-full bg-sky-900" :style="{ width: p.bar + '%' }"></div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Bottom strip: stock movement sparkline-style -->
        <div class="border border-stone-300 bg-white">
            <div class="px-3 py-2 border-b border-stone-200 bg-stone-100 flex items-center justify-between">
                <h2 class="text-[11px] font-semibold text-stone-700 uppercase tracking-wider">Stock movement · last 14 days</h2>
                <div class="flex gap-3 text-[11px]">
                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 bg-sky-900"></span>In</span>
                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 bg-amber-500"></span>Out</span>
                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 bg-stone-300"></span>Net</span>
                </div>
            </div>
            <div class="p-3">
                <svg viewBox="0 0 600 100" class="w-full h-24" preserveAspectRatio="none">
                    <polyline fill="none" stroke="#0c4a6e" stroke-width="1.5" points="0,60 40,55 80,40 120,45 160,30 200,35 240,25 280,30 320,20 360,25 400,15 440,20 480,10 520,15 560,8 600,5"/>
                    <polyline fill="none" stroke="#f59e0b" stroke-width="1.5" points="0,70 40,68 80,60 120,55 160,50 200,45 240,40 280,42 320,38 360,35 400,30 440,32 480,25 520,28 560,20 600,22"/>
                    <polyline fill="none" stroke="#d6d3d1" stroke-width="1.5" stroke-dasharray="3,2" points="0,75 40,72 80,68 120,65 160,58 200,55 240,50 280,48 320,45 360,42 400,38 440,38 480,32 520,32 560,28 600,25"/>
                </svg>
                <div class="flex justify-between text-[10px] va-mono text-stone-500 mt-1">
                    <span>Jun 23</span><span>Jun 30</span><span>Jul 7</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const date = new Date().toLocaleDateString('en-GB', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });

const actionItems = [
    { id: 1, severity: 'critical', title: 'Approve PO-2024-0184 · BrightFoods Co.', context: '$4,820 · 12 line items · ETA 3 days', ref: 'PO-0184', age: '2h' },
    { id: 2, severity: 'critical', title: 'Batch BTH-0921 expired in warehouse B', context: 'Soy sauce 750ml · 24 units remaining', ref: 'BTH-0921', age: '4h' },
    { id: 3, severity: 'warning', title: 'Low stock:Instant Noodles (Pack of 30)', context: 'Reorder point: 50 · On hand: 18 · Phnom Penh', ref: 'SKU-2287', age: '6h' },
    { id: 4, severity: 'warning', title: 'Draft sale DSA-334 awaiting payment', context: 'Customer A.Reach · 3 items · $284.00', ref: 'DSA-334', age: '1d' },
    { id: 5, severity: 'info', title: 'Transfer TRF-77 ready to dispatch', context: 'Phnom Penh → Siem Reap · 8 batches', ref: 'TRF-77', age: '2d' },
];

const topProducts = [
    { sku: 'SKU-1014', name: 'Coca-Cola 325ml Can', units: 1840, bar: 100 },
    { sku: 'SKU-2287', name: 'Instant Noodles · 30pk', units: 1245, bar: 68 },
    { sku: 'SKU-0421', name: 'Sunlight Soap 110g', units: 982, bar: 53 },
    { sku: 'SKU-3301', name: 'ABC Soy Sauce 750ml', units: 718, bar: 39 },
    { sku: 'SKU-5602', name: 'Pokka Green Tea 500ml', units: 564, bar: 31 },
];

function dotClass(s) {
    return {
        critical: 'va-dot-critical',
        warning: 'va-dot-warning',
        info: 'va-dot-info',
        fresh: 'va-dot-fresh',
    }[s];
}
</script>
