<template>
    <div class="vb space-y-4">
        <!-- Header -->
        <div class="flex items-end justify-between">
            <div>
                <h1>Batches</h1>
                <p class="text-sm text-zinc-500 mt-1">287 active batches across 3 warehouses</p>
            </div>
            <div class="flex items-center gap-2">
                <button class="h-9 px-3 text-xs font-medium border border-zinc-200 bg-white rounded-md hover:bg-zinc-50 flex items-center gap-1.5 text-zinc-700">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export
                </button>
                <button class="vb-btn-primary">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Receive batch
                </button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex items-center gap-1 border-b border-zinc-200">
            <button v-for="t in tabs" :key="t.id"
                    class="px-3 py-2 text-xs font-medium border-b-2 -mb-px"
                    :class="t.id === active ? 'border-zinc-950 text-zinc-950' : 'border-transparent text-zinc-500 hover:text-zinc-900'">
                {{ t.label }}
                <span v-if="t.count !== null" class="ml-1.5 vb-mono text-[10px]" :class="t.id === active ? 'text-zinc-500' : 'text-zinc-400'">{{ t.count }}</span>
            </button>
        </div>

        <!-- Toolbar -->
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-2 px-2.5 h-9 border border-zinc-200 bg-white rounded-md flex-1 max-w-sm">
                <svg class="w-3.5 h-3.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input class="bg-transparent border-0 outline-none text-xs flex-1 text-zinc-900 placeholder-zinc-400" placeholder="Search by batch, product, supplier…"/>
            </div>
            <select class="h-9 px-3 text-xs border border-zinc-200 bg-white rounded-md text-zinc-700">
                <option>All warehouses</option>
                <option>Phnom Penh</option>
                <option>Siem Reap</option>
                <option>Sihanoukville</option>
            </select>
            <select class="h-9 px-3 text-xs border border-zinc-200 bg-white rounded-md text-zinc-700">
                <option>All categories</option>
                <option>Beverages</option>
                <option>Food</option>
                <option>Household</option>
            </select>
            <button class="h-9 w-9 border border-zinc-200 bg-white rounded-md flex items-center justify-center text-zinc-600 hover:bg-zinc-50">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
            </button>
        </div>

        <!-- Table -->
        <div class="vb-card">
            <div class="vb-row vb-row-header" style="grid-template-columns: 110px 1fr 130px 90px 90px 110px 100px 50px;">
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
                 class="vb-row"
                 :style="{ gridTemplateColumns: '110px 1fr 130px 90px 90px 110px 100px 50px' }">
                <span class="vb-mono text-zinc-700 font-semibold">{{ b.batch }}</span>
                <div class="min-w-0">
                    <div class="font-medium text-zinc-950 truncate">{{ b.product }}</div>
                    <div class="vb-mono text-[10px] text-zinc-500">{{ b.sku }} · {{ b.unit }}</div>
                </div>
                <span class="text-xs text-zinc-700 truncate">{{ b.warehouse }}</span>
                <span class="text-right vb-mono font-semibold text-zinc-950">{{ b.qty }}</span>
                <span class="text-right vb-mono" :class="b.remaining < b.qty * 0.3 ? 'text-amber-600 font-bold' : 'text-zinc-700'">
                    {{ b.remaining }}
                </span>
                <div>
                    <div class="vb-mono text-xs font-semibold" :style="{ color: expiryColor(b.daysToExpiry) }">
                        {{ b.expiry }}
                    </div>
                    <div class="text-[10px] text-zinc-500 vb-mono">{{ expiryRel(b.daysToExpiry) }}</div>
                </div>
                <span class="vb-pill" :class="pillClass(b.daysToExpiry)">
                    <span class="vb-dot" :class="dotClass(b.daysToExpiry)"></span>
                    {{ statusLabel(b.daysToExpiry) }}
                </span>
                <button class="text-zinc-400 hover:text-zinc-900 w-7 h-7 rounded hover:bg-zinc-100 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/></svg>
                </button>
            </div>

            <div class="px-4 py-3 border-t border-zinc-100 flex items-center justify-between text-xs text-zinc-600">
                <span>Showing <span class="vb-mono font-semibold">1–12</span> of <span class="vb-mono font-semibold">287</span></span>
                <div class="flex gap-1">
                    <button class="px-2.5 py-1 border border-zinc-200 bg-white rounded hover:bg-zinc-50">‹</button>
                    <button class="px-2.5 py-1 bg-zinc-950 text-white font-semibold rounded">1</button>
                    <button class="px-2.5 py-1 border border-zinc-200 bg-white rounded hover:bg-zinc-50">2</button>
                    <button class="px-2.5 py-1 border border-zinc-200 bg-white rounded hover:bg-zinc-50">3</button>
                    <button class="px-2.5 py-1 border border-zinc-200 bg-white rounded hover:bg-zinc-50">…</button>
                    <button class="px-2.5 py-1 border border-zinc-200 bg-white rounded hover:bg-zinc-50">›</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const active = ref('all');
const tabs = [
    { id: 'all',      label: 'All batches', count: 287 },
    { id: 'expiring', label: 'Expiring',    count: 46 },
    { id: 'expired',  label: 'Expired',     count: 3 },
    { id: 'fresh',    label: 'Fresh',       count: 238 },
];

const batches = [
    { id: 1, batch: 'BTH-0921', product: 'ABC Soy Sauce', sku: 'SKU-3301', unit: '750ml bottle', warehouse: 'Phnom Penh', qty: 240, remaining: 24, expiry: 'Aug 12, 2025', daysToExpiry: -3 },
    { id: 2, batch: 'BTH-0918', product: 'Coca-Cola Original', sku: 'SKU-1014', unit: '325ml · 24/case', warehouse: 'Phnom Penh', qty: 480, remaining: 412, expiry: 'Aug 15, 2025', daysToExpiry: 1 },
    { id: 3, batch: 'BTH-0917', product: 'Instant Noodles · Chicken', sku: 'SKU-2287', unit: '85g · 30/case', warehouse: 'Phnom Penh', qty: 60, remaining: 18, expiry: 'Aug 17, 2025', daysToExpiry: 3 },
    { id: 4, batch: 'BTH-0915', product: 'Sunlight Soap', sku: 'SKU-0421', unit: '110g · 72/case', warehouse: 'Siem Reap', qty: 144, remaining: 96, expiry: 'Aug 21, 2025', daysToExpiry: 7 },
    { id: 5, batch: 'BTH-0912', product: 'Pokka Green Tea', sku: 'SKU-5602', unit: '500ml bottle', warehouse: 'Phnom Penh', qty: 360, remaining: 248, expiry: 'Aug 25, 2025', daysToExpiry: 11 },
    { id: 6, batch: 'BTH-0910', product: 'Milo UHT', sku: 'SKU-2204', unit: '200ml · 24/case', warehouse: 'Sihanoukville', qty: 192, remaining: 156, expiry: 'Sep 02, 2025', daysToExpiry: 19 },
    { id: 7, batch: 'BTH-0908', product: 'Angkor Beer', sku: 'SKU-8801', unit: '330ml · 24/case', warehouse: 'Siem Reap', qty: 480, remaining: 320, expiry: 'Sep 15, 2025', daysToExpiry: 32 },
    { id: 8, batch: 'BTH-0905', product: 'Number One Coffee', sku: 'SKU-7720', unit: '20g · 24 sticks', warehouse: 'Phnom Penh', qty: 96, remaining: 88, expiry: 'Oct 04, 2025', daysToExpiry: 51 },
    { id: 9, batch: 'BTH-0901', product: 'Khmer Rice', sku: 'SKU-0011', unit: '5kg bag', warehouse: 'Phnom Penh', qty: 200, remaining: 187, expiry: 'Jan 15, 2026', daysToExpiry: 154 },
    { id: 10, batch: 'BTH-0898', product: 'Lucky Brand Tofu', sku: 'SKU-4402', unit: '400g · 20/case', warehouse: 'Phnom Penh', qty: 80, remaining: 4, expiry: 'Aug 14, 2025', daysToExpiry: -1 },
    { id: 11, batch: 'BTH-0894', product: 'Coca-Cola Zero', sku: 'SKU-1016', unit: '325ml · 24/case', warehouse: 'Siem Reap', qty: 240, remaining: 210, expiry: 'Sep 28, 2025', daysToExpiry: 45 },
    { id: 12, batch: 'BTH-0890', product: 'ABC Fish Sauce', sku: 'SKU-3302', unit: '750ml bottle', warehouse: 'Sihanoukville', qty: 144, remaining: 132, expiry: 'Dec 20, 2025', daysToExpiry: 128 },
];

function statusLabel(d) {
    if (d < 0) return 'Expired';
    if (d <= 7) return 'Critical';
    if (d <= 30) return 'Warning';
    return 'Fresh';
}
function pillClass(d) {
    if (d < 0 || d <= 7) return 'vb-pill vb-pill-critical';
    if (d <= 30) return 'vb-pill vb-pill-warning';
    return 'vb-pill vb-pill-fresh';
}
function dotClass(d) {
    if (d < 0 || d <= 7) return 'vb-dot-critical';
    if (d <= 30) return 'vb-dot-warning';
    return 'vb-dot-fresh';
}
function expiryColor(d) {
    if (d < 0 || d <= 7) return '#b91c1c';
    if (d <= 30) return '#b45309';
    return '#18181b';
}
function expiryRel(d) {
    if (d < 0) return `${Math.abs(d)}d ago`;
    if (d === 0) return 'today';
    return `in ${d}d`;
}
</script>
