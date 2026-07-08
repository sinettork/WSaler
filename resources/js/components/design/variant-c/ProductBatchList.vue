<template>
    <div class="vc space-y-4">
        <!-- Header -->
        <div class="flex items-end justify-between">
            <div>
                <h1>Batches</h1>
                <p class="text-sm text-stone-600 mt-1">
                    287 active across 3 warehouses ·
                    <span class="font-bold text-red-700">3 expired</span> ·
                    <span class="font-bold text-amber-700">12 within 7 days</span>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <button class="h-10 px-4 text-sm font-medium border border-stone-200 bg-white rounded-lg hover:bg-stone-50 flex items-center gap-2 text-stone-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export
                </button>
                <button class="vc-btn-primary h-10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Receive batch
                </button>
            </div>
        </div>

        <!-- Status tabs (functional, not decorative) -->
        <div class="vc-card overflow-hidden">
            <div class="flex border-b border-stone-100">
                <button v-for="t in tabs" :key="t.id"
                        class="flex-1 px-5 py-3.5 flex items-center justify-center gap-2 text-sm font-medium border-b-2 -mb-px transition-colors"
                        :class="t.id === active ? 'border-orange-600 text-stone-950' : 'border-transparent text-stone-500 hover:text-stone-900'">
                    <span class="vc-dot" :class="t.dotClass"></span>
                    {{ t.label }}
                    <span class="vc-mono text-xs px-1.5 py-0.5 rounded" :class="t.id === active ? 'bg-stone-100' : 'bg-stone-50 text-stone-500'">{{ t.count }}</span>
                </button>
            </div>

            <!-- Toolbar -->
            <div class="px-5 py-3.5 flex items-center gap-2 border-b border-stone-100">
                <div class="flex items-center gap-2 px-3 h-9 border border-stone-200 bg-white rounded-lg flex-1 max-w-sm">
                    <svg class="w-4 h-4 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input class="bg-transparent border-0 outline-none text-sm flex-1 text-stone-900 placeholder-stone-400" placeholder="Search by batch, product, supplier…"/>
                </div>
                <select class="h-9 px-3 text-sm border border-stone-200 bg-white rounded-lg text-stone-700">
                    <option>All warehouses</option>
                    <option>Phnom Penh</option>
                    <option>Siem Reap</option>
                    <option>Sihanoukville</option>
                </select>
                <select class="h-9 px-3 text-sm border border-stone-200 bg-white rounded-lg text-stone-700">
                    <option>All categories</option>
                    <option>Beverages</option>
                    <option>Food</option>
                    <option>Household</option>
                </select>
            </div>

            <!-- Table -->
            <div class="vc-row vc-row-header" style="grid-template-columns: 120px 1fr 140px 90px 90px 130px 110px 50px;">
                <span>Batch No.</span>
                <span>Product</span>
                <span>Warehouse</span>
                <span class="text-right">Qty</span>
                <span class="text-right">Remaining</span>
                <span>Expiry</span>
                <span>Status</span>
                <span></span>
            </div>

            <div v-for="b in batches" :key="b.id"
                 class="vc-row"
                 :class="rowBg(b.daysToExpiry)"
                 :style="{ gridTemplateColumns: '120px 1fr 140px 90px 90px 130px 110px 50px' }">
                <span class="vc-mono text-stone-700 font-semibold">{{ b.batch }}</span>
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-7 h-7 rounded-md flex items-center justify-center text-xs font-bold shrink-0" :style="{ background: b.swatch, color: b.swatchFg }">
                        {{ b.initial }}
                    </div>
                    <div class="min-w-0">
                        <div class="font-medium text-stone-950 truncate">{{ b.product }}</div>
                        <div class="vc-mono text-[10px] text-stone-500">{{ b.sku }} · {{ b.unit }}</div>
                    </div>
                </div>
                <span class="text-sm text-stone-700 truncate">{{ b.warehouse }}</span>
                <span class="text-right vc-mono font-semibold text-stone-950">{{ b.qty }}</span>
                <span class="text-right vc-mono" :class="b.remaining < b.qty * 0.3 ? 'text-amber-700 font-bold' : 'text-stone-700'">
                    {{ b.remaining }}
                </span>
                <div>
                    <div class="vc-mono text-sm font-semibold" :style="{ color: expiryColor(b.daysToExpiry) }">
                        {{ b.expiry }}
                    </div>
                    <div class="text-[10px] text-stone-500 vc-mono">{{ expiryRel(b.daysToExpiry) }}</div>
                </div>
                <span class="vc-pill" :class="pillClass(b.daysToExpiry)">{{ statusLabel(b.daysToExpiry) }}</span>
                <button class="text-stone-400 hover:text-stone-900 w-7 h-7 rounded hover:bg-stone-100 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/></svg>
                </button>
            </div>

            <div class="px-5 py-3.5 border-t border-stone-100 flex items-center justify-between text-sm text-stone-600">
                <span>Showing <span class="vc-mono font-semibold">1–12</span> of <span class="vc-mono font-semibold">287</span> batches</span>
                <div class="flex gap-1">
                    <button class="px-3 py-1 border border-stone-200 bg-white rounded-lg hover:bg-stone-50">‹</button>
                    <button class="px-3 py-1 bg-stone-950 text-white font-semibold rounded-lg">1</button>
                    <button class="px-3 py-1 border border-stone-200 bg-white rounded-lg hover:bg-stone-50">2</button>
                    <button class="px-3 py-1 border border-stone-200 bg-white rounded-lg hover:bg-stone-50">3</button>
                    <button class="px-3 py-1 border border-stone-200 bg-white rounded-lg hover:bg-stone-50">…</button>
                    <button class="px-3 py-1 border border-stone-200 bg-white rounded-lg hover:bg-stone-50">›</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const active = ref('all');
const tabs = [
    { id: 'all',      label: 'All',         count: 287, dotClass: 'vc-dot-info' },
    { id: 'expired',  label: 'Expired',     count: 3,   dotClass: 'vc-dot-critical' },
    { id: 'critical', label: '≤ 7 days',    count: 12,  dotClass: 'vc-dot-critical' },
    { id: 'warning',  label: '≤ 30 days',   count: 46,  dotClass: 'vc-dot-warning' },
    { id: 'fresh',    label: 'Fresh',       count: 238, dotClass: 'vc-dot-fresh' },
];

const swatches = {
    'SKU-3301': ['#fef3c7', '#b45309', 'A'],
    'SKU-1014': ['#fee2e2', '#b91c1c', 'C'],
    'SKU-2287': ['#fef3c7', '#b45309', 'I'],
    'SKU-0421': ['#dbeafe', '#1e40af', 'S'],
    'SKU-5602': ['#dcfce7', '#166534', 'P'],
    'SKU-2204': ['#fce7f3', '#9d174d', 'M'],
    'SKU-8801': ['#fef3c7', '#b45309', 'A'],
    'SKU-7720': ['#ffedd5', '#9a3412', 'N'],
    'SKU-0011': ['#dcfce7', '#166534', 'K'],
    'SKU-4402': ['#f3e8ff', '#6b21a8', 'L'],
    'SKU-1016': ['#fee2e2', '#b91c1c', 'C'],
    'SKU-3302': ['#fef3c7', '#b45309', 'A'],
};

const rawBatches = [
    { batch: 'BTH-0921', product: 'ABC Soy Sauce', sku: 'SKU-3301', unit: '750ml bottle', warehouse: 'Phnom Penh', qty: 240, remaining: 24, expiry: 'Aug 12, 2025', daysToExpiry: -3 },
    { batch: 'BTH-0918', product: 'Coca-Cola Original', sku: 'SKU-1014', unit: '325ml · 24/case', warehouse: 'Phnom Penh', qty: 480, remaining: 412, expiry: 'Aug 15, 2025', daysToExpiry: 1 },
    { batch: 'BTH-0917', product: 'Instant Noodles · Chicken', sku: 'SKU-2287', unit: '85g · 30/case', warehouse: 'Phnom Penh', qty: 60, remaining: 18, expiry: 'Aug 17, 2025', daysToExpiry: 3 },
    { batch: 'BTH-0915', product: 'Sunlight Soap', sku: 'SKU-0421', unit: '110g · 72/case', warehouse: 'Siem Reap', qty: 144, remaining: 96, expiry: 'Aug 21, 2025', daysToExpiry: 7 },
    { batch: 'BTH-0912', product: 'Pokka Green Tea', sku: 'SKU-5602', unit: '500ml bottle', warehouse: 'Phnom Penh', qty: 360, remaining: 248, expiry: 'Aug 25, 2025', daysToExpiry: 11 },
    { batch: 'BTH-0910', product: 'Milo UHT', sku: 'SKU-2204', unit: '200ml · 24/case', warehouse: 'Sihanoukville', qty: 192, remaining: 156, expiry: 'Sep 02, 2025', daysToExpiry: 19 },
    { batch: 'BTH-0908', product: 'Angkor Beer', sku: 'SKU-8801', unit: '330ml · 24/case', warehouse: 'Siem Reap', qty: 480, remaining: 320, expiry: 'Sep 15, 2025', daysToExpiry: 32 },
    { batch: 'BTH-0905', product: 'Number One Coffee', sku: 'SKU-7720', unit: '20g · 24 sticks', warehouse: 'Phnom Penh', qty: 96, remaining: 88, expiry: 'Oct 04, 2025', daysToExpiry: 51 },
    { batch: 'BTH-0901', product: 'Khmer Rice', sku: 'SKU-0011', unit: '5kg bag', warehouse: 'Phnom Penh', qty: 200, remaining: 187, expiry: 'Jan 15, 2026', daysToExpiry: 154 },
    { batch: 'BTH-0898', product: 'Lucky Brand Tofu', sku: 'SKU-4402', unit: '400g · 20/case', warehouse: 'Phnom Penh', qty: 80, remaining: 4, expiry: 'Aug 14, 2025', daysToExpiry: -1 },
    { batch: 'BTH-0894', product: 'Coca-Cola Zero', sku: 'SKU-1016', unit: '325ml · 24/case', warehouse: 'Siem Reap', qty: 240, remaining: 210, expiry: 'Sep 28, 2025', daysToExpiry: 45 },
    { batch: 'BTH-0890', product: 'ABC Fish Sauce', sku: 'SKU-3302', unit: '750ml bottle', warehouse: 'Sihanoukville', qty: 144, remaining: 132, expiry: 'Dec 20, 2025', daysToExpiry: 128 },
];

const batches = rawBatches.map(b => {
    const s = swatches[b.sku] || ['#f5f5f4', '#57534e', '?'];
    return { ...b, swatch: s[0], swatchFg: s[1], initial: s[2] };
});

function statusLabel(d) {
    if (d < 0) return 'Expired';
    if (d <= 7) return 'Critical';
    if (d <= 30) return 'Warning';
    return 'Fresh';
}
function pillClass(d) {
    if (d < 0 || d <= 7) return 'vc-pill vc-pill-critical';
    if (d <= 30) return 'vc-pill vc-pill-warning';
    return 'vc-pill vc-pill-fresh';
}
function expiryColor(d) {
    if (d < 0 || d <= 7) return '#b91c1c';
    if (d <= 30) return '#854d0e';
    return '#1c1917';
}
function expiryRel(d) {
    if (d < 0) return `${Math.abs(d)}d ago`;
    if (d === 0) return 'today';
    return `in ${d}d`;
}
function rowBg(d) {
    if (d < 0) return '!bg-red-50/50';
    return '';
}
</script>
