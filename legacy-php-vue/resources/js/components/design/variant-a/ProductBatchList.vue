<template>
    <div class="va space-y-3">
        <!-- Page header strip -->
        <div class="flex items-end justify-between">
            <div>
                <div class="text-[10px] uppercase tracking-widest text-stone-500">Inventory</div>
                <h1 class="text-xl font-bold text-stone-900">Batches</h1>
                <div class="text-xs text-stone-500 mt-0.5">
                    287 active batches · 12 expiring within 7 days · 3 expired
                </div>
            </div>
            <div class="flex gap-2">
                <button class="h-8 px-3 text-xs font-semibold border border-stone-300 bg-white hover:bg-stone-100 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export
                </button>
                <button class="h-8 px-3 text-xs font-semibold border border-stone-300 bg-white hover:bg-stone-100">Filter</button>
                <button class="h-8 px-3 text-xs font-semibold bg-sky-900 text-white hover:bg-sky-800 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Receive batch
                </button>
            </div>
        </div>

        <!-- Quick filter chips -->
        <div class="flex items-center gap-1.5 text-xs">
            <button class="px-2.5 py-1 bg-stone-800 text-white font-semibold">All · 287</button>
            <button class="px-2.5 py-1 border border-stone-300 bg-white hover:bg-stone-100 flex items-center gap-1.5">
                <span class="va-dot va-dot-critical"></span> Expired · 3
            </button>
            <button class="px-2.5 py-1 border border-stone-300 bg-white hover:bg-stone-100 flex items-center gap-1.5">
                <span class="va-dot va-dot-warning"></span> ≤ 7 days · 12
            </button>
            <button class="px-2.5 py-1 border border-stone-300 bg-white hover:bg-stone-100 flex items-center gap-1.5">
                <span class="va-dot va-dot-info"></span> ≤ 30 days · 34
            </button>
            <button class="px-2.5 py-1 border border-stone-300 bg-white hover:bg-stone-100 flex items-center gap-1.5">
                <span class="va-dot va-dot-fresh"></span> Fresh · 238
            </button>
            <span class="flex-1"></span>
            <span class="text-stone-500">Warehouse:</span>
            <select class="border border-stone-300 px-2 py-1 text-xs bg-white">
                <option>All warehouses</option>
                <option>Phnom Penh (Main)</option>
                <option>Siem Reap</option>
                <option>Sihanoukville</option>
            </select>
        </div>

        <!-- Dense table -->
        <div class="border border-stone-300 bg-white">
            <!-- Header -->
            <div class="va-row va-row-header" style="grid-template-columns: 110px 1fr 120px 90px 90px 100px 100px 70px;">
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
                 class="va-row"
                 :style="{ gridTemplateColumns: '110px 1fr 120px 90px 90px 100px 100px 70px' }">
                <span class="va-mono text-stone-700 font-semibold">{{ b.batch }}</span>
                <div class="min-w-0">
                    <div class="font-semibold text-stone-900 truncate">{{ b.product }}</div>
                    <div class="va-mono text-[10px] text-stone-500">{{ b.sku }} · {{ b.unit }}</div>
                </div>
                <span class="text-xs text-stone-700 truncate">{{ b.warehouse }}</span>
                <span class="text-right va-mono font-semibold">{{ b.qty }}</span>
                <span class="text-right va-mono" :class="b.remaining < b.qty * 0.3 ? 'text-amber-700 font-bold' : 'text-stone-700'">
                    {{ b.remaining }}
                </span>
                <div>
                    <div class="va-mono text-xs" :class="expiryColor(b.daysToExpiry)">
                        {{ b.expiry }}
                    </div>
                    <div class="text-[10px] text-stone-500 va-mono">{{ expiryRel(b.daysToExpiry) }}</div>
                </div>
                <span class="va-badge" :class="statusClass(b.daysToExpiry)">{{ statusLabel(b.daysToExpiry) }}</span>
                <button class="text-stone-400 hover:text-stone-900">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/></svg>
                </button>
            </div>

            <!-- Footer / pagination -->
            <div class="px-3 py-2 border-t border-stone-200 bg-stone-50 flex items-center justify-between text-[11px] text-stone-600">
                <span>Showing 1–12 of 287</span>
                <div class="flex gap-1">
                    <button class="px-2 py-0.5 border border-stone-300 bg-white">‹ Prev</button>
                    <button class="px-2 py-0.5 bg-stone-800 text-white font-semibold">1</button>
                    <button class="px-2 py-0.5 border border-stone-300 bg-white">2</button>
                    <button class="px-2 py-0.5 border border-stone-300 bg-white">3</button>
                    <button class="px-2 py-0.5 border border-stone-300 bg-white">…</button>
                    <button class="px-2 py-0.5 border border-stone-300 bg-white">24</button>
                    <button class="px-2 py-0.5 border border-stone-300 bg-white">Next ›</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const batches = [
    { id: 1, batch: 'BTH-0921', product: 'ABC Soy Sauce', sku: 'SKU-3301', unit: '750ml bottle', warehouse: 'Phnom Penh', qty: 240, remaining: 24, expiry: '2025-08-12', daysToExpiry: -3 },
    { id: 2, batch: 'BTH-0918', product: 'Coca-Cola Original', sku: 'SKU-1014', unit: '325ml can · 24/case', warehouse: 'Phnom Penh', qty: 480, remaining: 412, expiry: '2025-08-15', daysToExpiry: 1 },
    { id: 3, batch: 'BTH-0917', product: 'Instant Noodles · Chicken', sku: 'SKU-2287', unit: '85g · 30/case', warehouse: 'Phnom Penh', qty: 60, remaining: 18, expiry: '2025-08-17', daysToExpiry: 3 },
    { id: 4, batch: 'BTH-0915', product: 'Sunlight Soap', sku: 'SKU-0421', unit: '110g bar · 72/case', warehouse: 'Siem Reap', qty: 144, remaining: 96, expiry: '2025-08-21', daysToExpiry: 7 },
    { id: 5, batch: 'BTH-0912', product: 'Pokka Green Tea', sku: 'SKU-5602', unit: '500ml bottle', warehouse: 'Phnom Penh', qty: 360, remaining: 248, expiry: '2025-08-25', daysToExpiry: 11 },
    { id: 6, batch: 'BTH-0910', product: 'Milo UHT', sku: 'SKU-2204', unit: '200ml · 24/case', warehouse: 'Sihanoukville', qty: 192, remaining: 156, expiry: '2025-09-02', daysToExpiry: 19 },
    { id: 7, batch: 'BTH-0908', product: 'Angkor Beer', sku: 'SKU-8801', unit: '330ml can · 24/case', warehouse: 'Siem Reap', qty: 480, remaining: 320, expiry: '2025-09-15', daysToExpiry: 32 },
    { id: 8, batch: 'BTH-0905', product: 'Number One Coffee', sku: 'SKU-7720', unit: '20g · 24 sticks', warehouse: 'Phnom Penh', qty: 96, remaining: 88, expiry: '2025-10-04', daysToExpiry: 51 },
    { id: 9, batch: 'BTH-0901', product: 'Khmer Rice', sku: 'SKU-0011', unit: '5kg bag', warehouse: 'Phnom Penh', qty: 200, remaining: 187, expiry: '2026-01-15', daysToExpiry: 154 },
    { id: 10, batch: 'BTH-0898', product: 'Lucky Brand Tofu', sku: 'SKU-4402', unit: '400g · 20/case', warehouse: 'Phnom Penh', qty: 80, remaining: 4, expiry: '2025-08-14', daysToExpiry: -1 },
    { id: 11, batch: 'BTH-0894', product: 'Coca-Cola Zero', sku: 'SKU-1016', unit: '325ml can · 24/case', warehouse: 'Siem Reap', qty: 240, remaining: 210, expiry: '2025-09-28', daysToExpiry: 45 },
    { id: 12, batch: 'BTH-0890', product: 'ABC Fish Sauce', sku: 'SKU-3302', unit: '750ml bottle', warehouse: 'Sihanoukville', qty: 144, remaining: 132, expiry: '2025-12-20', daysToExpiry: 128 },
];

function statusLabel(d) {
    if (d < 0) return 'Expired';
    if (d <= 7) return 'Critical';
    if (d <= 30) return 'Warning';
    return 'Fresh';
}
function statusClass(d) {
    if (d < 0) return 'va-badge-critical';
    if (d <= 7) return 'va-badge-critical';
    if (d <= 30) return 'va-badge-warning';
    return 'va-badge-fresh';
}
function expiryColor(d) {
    if (d < 0) return 'text-red-700 font-bold';
    if (d <= 7) return 'text-red-700 font-bold';
    if (d <= 30) return 'text-amber-700 font-semibold';
    return 'text-stone-700';
}
function expiryRel(d) {
    if (d < 0) return `${Math.abs(d)}d ago`;
    if (d === 0) return 'today';
    return `in ${d}d`;
}
</script>
