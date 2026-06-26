<template>
    <div class="h-[calc(100vh-7rem)] flex flex-col gap-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Point of Sale</h1>
                <p class="text-sm text-slate-500">Scan a barcode or search to add items to the sale.</p>
            </div>
            <div class="flex items-center gap-2">
                <select
                    v-model="warehouseId"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                >
                    <option value="" disabled>Select warehouse&hellip;</option>
                    <option v-for="w in warehouses" :key="w.id" :value="w.id">
                        {{ w.code }} \u2014 {{ w.name }}
                    </option>
                </select>
            </div>
        </div>

        <div v-if="errorBanner" class="rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700 flex items-start gap-2">
            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>
            </svg>
            <span>{{ errorBanner }}</span>
            <button type="button" class="ml-auto text-rose-500 hover:text-rose-700" @click="errorBanner = ''" aria-label="Dismiss">&times;</button>
        </div>

        <div class="flex-1 grid grid-cols-1 lg:grid-cols-5 gap-4 min-h-0">
            <div class="lg:col-span-3 flex flex-col gap-3 min-h-0">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        ref="searchInputRef"
                        v-model="search"
                        type="search"
                        placeholder="Search by name, SKU, or scan barcode\u2026"
                        class="w-full pl-10 pr-4 py-2.5 text-sm rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                        @keydown.enter="onSearchEnter"
                        @input="onSearchInput"
                    />
                </div>

                <div class="flex-1 bg-white rounded-xl border border-slate-200 overflow-y-auto">
                    <div v-if="searching" class="p-6">
                        <Spinner size="sm" :center="false" label="Searching products\u2026" />
                    </div>
                    <EmptyState
                        v-else-if="searchResults.length === 0 && search"
                        title="No products found"
                        :description="`Nothing matches ${search}.`"
                        icon="search"
                    />
                    <EmptyState
                        v-else-if="!search"
                        title="Scan or search to begin"
                        description="Type a product name, paste an SKU, or scan a barcode. Results appear here."
                        icon="inbox"
                    />
                    <ul v-else class="divide-y divide-slate-100">
                        <template v-for="p in searchResults" :key="p.id">
                            <!-- Product without variations: one clickable row -->
                            <li v-if="!p.variations || p.variations.length === 0">
                                <button
                                    type="button"
                                    class="w-full text-left px-4 py-3 hover:bg-slate-50 flex items-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed"
                                    :disabled="(p.total_stock ?? 0) <= 0"
                                    @click="addProduct(p)"
                                >
                                    <div class="shrink-0 w-10 h-10 rounded-lg bg-slate-50 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-slate-900 truncate">{{ p.name }}</p>
                                        <p class="text-xs text-slate-500">
                                            {{ p.sku || '\u2014' }}
                                            <span v-if="p.category"> \u00b7 {{ p.category.name }}</span>
                                        </p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p class="font-semibold text-slate-900">{{ formatCurrency(priceFor(p)) }}</p>
                                        <p class="text-xs" :class="(p.total_stock ?? 0) > 0 ? 'text-slate-500' : 'text-rose-600'">
                                            {{ (p.total_stock ?? 0) > 0 ? `${p.total_stock} in stock` : 'Out of stock' }}
                                        </p>
                                    </div>
                                </button>
                            </li>

                            <!-- Product with variations: one row per variation -->
                            <template v-else>
                                <li v-for="v in p.variations" :key="`${p.id}-${v.id}`">
                                    <button
                                        type="button"
                                        class="w-full text-left px-4 py-3 hover:bg-slate-50 flex items-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed"
                                        :disabled="(p.total_stock ?? 0) <= 0"
                                        @click="addProduct(p, v)"
                                    >
                                        <div class="shrink-0 w-10 h-10 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-slate-900 truncate">
                                                {{ p.name }}
                                                <span class="text-slate-500 font-normal">\u2014 {{ v.value }}</span>
                                            </p>
                                            <p class="text-xs text-slate-500 truncate">
                                                <span v-if="v.barcode">{{ v.barcode }} \u00b7 </span>
                                                <span v-if="v.sku_suffix">SKU: {{ v.sku_suffix }} \u00b7 </span>
                                                <span v-if="(v.quantity_multiplier ?? 1) > 1" class="font-medium text-brand-600">\u00d7{{ v.quantity_multiplier }} base units</span>
                                            </p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <p class="font-semibold text-slate-900">{{ formatCurrency(priceFor(p, v)) }}</p>
                                            <p class="text-xs" :class="(p.total_stock ?? 0) > 0 ? 'text-slate-500' : 'text-rose-600'">
                                                {{ (p.total_stock ?? 0) > 0 ? `${p.total_stock} in stock` : 'Out of stock' }}
                                            </p>
                                        </div>
                                    </button>
                                </li>
                            </template>
                        </template>
                    </ul>
                </div>
            </div>

            <div class="lg:col-span-2 flex flex-col gap-3 min-h-0">
                <div class="bg-white rounded-xl border border-slate-200 p-3">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Customer</p>
                        <button v-if="cart.customer.value" type="button" class="text-xs text-brand-600 hover:text-brand-700" @click="cart.setCustomer(null)">
                            Clear
                        </button>
                    </div>
                    <div v-if="cart.customer.value" class="flex items-center gap-2 text-sm">
                        <div class="w-8 h-8 rounded-full bg-brand-50 text-brand-700 flex items-center justify-center font-semibold text-xs">
                            {{ cart.customer.value.name?.[0]?.toUpperCase() || '?' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-slate-900 truncate">{{ cart.customer.value.name }}</p>
                            <p class="text-xs text-slate-500">
                                {{ cart.customer.value.code }}
                                <span v-if="cart.customer.value.credit_limit > 0">
                                    \u00b7 Credit ${{ cart.customer.value.current_balance }}/{{ cart.customer.value.credit_limit }}
                                </span>
                            </p>
                        </div>
                    </div>
                    <div v-else class="relative">
                        <input
                            v-model="customerSearch"
                            type="search"
                            placeholder="Search customer\u2026"
                            class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                            @input="onCustomerSearch"
                            @focus="customerResultsOpen = true"
                            @blur="closeCustomerResults"
                        />
                        <ul
                            v-if="customerResultsOpen && customerResults.length > 0"
                            class="absolute z-10 mt-1 w-full max-h-60 overflow-y-auto bg-white rounded-lg ring-1 ring-slate-200 shadow-lg"
                        >
                            <li v-for="c in customerResults" :key="c.id">
                                <button
                                    type="button"
                                    class="w-full text-left px-3 py-2 text-sm hover:bg-slate-50 flex items-center gap-2"
                                    @mousedown.prevent="selectCustomer(c)"
                                >
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-slate-900 truncate">{{ c.name }}</p>
                                        <p class="text-xs text-slate-500">{{ c.code }}</p>
                                    </div>
                                    <BaseBadge v-if="c.type" :variant="customerTypeVariant(c.type)">{{ c.type }}</BaseBadge>
                                </button>
                            </li>
                        </ul>
                        <p v-if="customerResults.length === 0 && customerSearch" class="absolute z-10 mt-1 w-full bg-white rounded-lg ring-1 ring-slate-200 px-3 py-2 text-sm text-slate-500 shadow-lg">
                            No customers match &quot;{{ customerSearch }}&quot;.
                        </p>
                    </div>
                </div>

                <div class="flex-1 bg-white rounded-xl border border-slate-200 flex flex-col min-h-0">
                    <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                            Cart <span v-if="cart.itemCount.value > 0" class="text-slate-400">({{ cart.itemCount.value }})</span>
                        </p>
                        <button v-if="!cart.isEmpty.value" type="button" class="text-xs text-rose-600 hover:text-rose-700" @click="cart.clear()">
                            Clear all
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto">
                        <EmptyState
                            v-if="cart.isEmpty.value"
                            title="Cart is empty"
                            description="Add products from the left to start the sale."
                            icon="box"
                        />
                        <ul v-else class="divide-y divide-slate-100">
                            <li v-for="line in cart.lines.value" :key="line.id" class="px-4 py-3">
                                <div class="flex items-start gap-2">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-slate-900 truncate">
                                            {{ line.product_name }}
                                            <span v-if="line.variation_label" class="text-slate-500 font-normal">\u2014 {{ line.variation_label }}</span>
                                        </p>
                                        <p class="text-xs text-slate-500">
                                            {{ line.unit_name }} \u00b7 {{ line.product_sku }}
                                            <span v-if="(line.quantity_multiplier ?? 1) > 1" class="ml-1 px-1.5 py-0.5 rounded bg-brand-50 text-brand-700 font-medium">
                                                \u00d7{{ line.quantity_multiplier }} base units each
                                            </span>
                                        </p>
                                    </div>
                                    <button type="button" class="text-slate-400 hover:text-rose-600" aria-label="Remove line" @click="cart.removeLine(line.id)">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                                <div class="mt-2 flex items-center gap-2">
                                    <div class="flex items-center rounded-md border border-slate-300">
                                        <button type="button" class="px-2 py-1 text-slate-500 hover:text-slate-700" @click="cart.updateLineQuantity(line.id, line.quantity - 1)">\u2212</button>
                                        <input
                                            type="number"
                                            min="1"
                                            :value="line.quantity"
                                            class="w-12 text-center text-sm border-x border-slate-300 py-1 focus:outline-none focus:ring-0"
                                            @change="cart.updateLineQuantity(line.id, $event.target.value)"
                                        />
                                        <button type="button" class="px-2 py-1 text-slate-500 hover:text-slate-700" @click="cart.updateLineQuantity(line.id, line.quantity + 1)">+</button>
                                    </div>
                                    <span class="text-slate-400 text-xs">\u00d7</span>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        :value="line.unit_price"
                                        class="w-20 text-right text-sm rounded-md border border-slate-300 py-1 px-2 focus:outline-none focus:ring-2 focus:ring-brand-500"
                                        @change="cart.updateLinePrice(line.id, $event.target.value)"
                                    />
                                    <span class="ml-auto text-sm font-semibold text-slate-900">
                                        {{ formatCurrency(line.line_total) }}
                                    </span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 p-4 space-y-3">
                    <dl class="space-y-1.5 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Subtotal</dt>
                            <dd class="font-medium text-slate-900">{{ formatCurrency(cart.subtotal.value) }}</dd>
                        </div>
                        <div class="flex justify-between items-center gap-2">
                            <dt class="text-slate-500">Discount</dt>
                            <dd class="flex items-center gap-1">
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    :value="cart.discount.value"
                                    class="w-24 text-right text-sm rounded-md border border-slate-300 py-0.5 px-2 focus:outline-none focus:ring-2 focus:ring-brand-500"
                                    @change="cart.setDiscount($event.target.value)"
                                />
                            </dd>
                        </div>
                        <div class="flex justify-between items-center gap-2">
                            <dt class="text-slate-500">Tax</dt>
                            <dd class="flex items-center gap-1">
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    :value="cart.tax.value"
                                    class="w-24 text-right text-sm rounded-md border border-slate-300 py-0.5 px-2 focus:outline-none focus:ring-2 focus:ring-brand-500"
                                    @change="cart.setTax($event.target.value)"
                                />
                            </dd>
                        </div>
                        <div class="flex justify-between pt-2 border-t border-slate-200">
                            <dt class="font-semibold text-slate-900">Total</dt>
                            <dd class="text-xl font-bold text-slate-900">{{ formatCurrency(cart.total.value) }}</dd>
                        </div>
                    </dl>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Payment</p>
                        <div class="grid grid-cols-3 gap-1.5 mb-2">
                            <button v-for="m in paymentMethods" :key="m.value" type="button"
                                class="px-2 py-2 text-xs font-medium rounded-md border border-slate-200 hover:border-brand-500 hover:text-brand-700 hover:bg-brand-50 transition-colors"
                                @click="quickAddPayment(m.value)">
                                {{ m.label }}
                            </button>
                        </div>
                        <ul v-if="cart.payments.value.length > 0" class="space-y-1.5">
                            <li v-for="(p, i) in cart.payments.value" :key="i" class="flex items-center gap-2 text-sm">
                                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-xs font-medium text-slate-700 uppercase">{{ p.method.replace('_', ' ') }}</span>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    :value="p.amount"
                                    class="flex-1 text-right rounded-md border border-slate-300 py-0.5 px-2 focus:outline-none focus:ring-2 focus:ring-brand-500"
                                    @change="updatePaymentAmount(i, $event.target.value)"
                                />
                                <button type="button" class="text-slate-400 hover:text-rose-600" aria-label="Remove payment" @click="cart.removePayment(i)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </li>
                        </ul>
                        <div class="mt-2 flex justify-between text-xs text-slate-500">
                            <span>Paid: <strong class="text-slate-900">{{ formatCurrency(cart.paid.value) }}</strong></span>
                            <span v-if="cart.balance.value > 0" class="text-amber-600">
                                Due: <strong>{{ formatCurrency(cart.remainingToPay.value) }}</strong>
                            </span>
                            <span v-else-if="cart.balance.value < 0" class="text-emerald-600">
                                Change: <strong>{{ formatCurrency(cart.changeDue.value) }}</strong>
                            </span>
                            <span v-else class="text-emerald-600">
                                <strong>Paid in full</strong>
                            </span>
                        </div>
                    </div>

                    <BaseButton
                        type="button"
                        block
                        :disabled="!canSubmit"
                        :loading="submitting"
                        @click="completeSale"
                    >
                        Complete sale \u2014 {{ formatCurrency(cart.total.value) }}
                    </BaseButton>
                </div>
            </div>
        </div>

        <PosReceipt
            v-if="completedSale"
            :sale="completedSale"
            @close="onReceiptClose"
            @new="onReceiptNew"
        />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { useApi } from '@/composables/useApi';
import { usePosCart } from '@/composables/usePosCart';
import { useSalesStore } from '@/stores/sales';
import { useWarehousesStore } from '@/stores/warehouses';
import { useToastStore } from '@/stores/toast';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseBadge from '@/components/ui/BaseBadge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import Spinner from '@/components/ui/Spinner.vue';
import PosReceipt from './PosReceipt.vue';

const api = useApi();
const salesStore = useSalesStore();
const warehousesStore = useWarehousesStore();
const toast = useToastStore();

const cart = usePosCart();

const search = ref('');
const searchResults = ref([]);
const searching = ref(false);
const searchInputRef = ref(null);

const warehouses = computed(() => warehousesStore.items || []);
const warehouseId = ref('');

const customerSearch = ref('');
const customerResults = ref([]);
const customerResultsOpen = ref(false);

const errorBanner = ref('');
const submitting = ref(false);
const completedSale = ref(null);

const paymentMethods = [
    { value: 'cash', label: 'Cash' },
    { value: 'card', label: 'Card' },
    { value: 'credit', label: 'Credit' },
    { value: 'bank_transfer', label: 'Bank' },
    { value: 'e_wallet', label: 'e-Wallet' },
];

let searchDebounce = null;
let customerDebounce = null;

/**
 * Compute the sell price. For a variation (pack), price = product.wholesale_price
 * x multiplier + variation.additional_price. For a plain product, use
 * wholesale_price (or retail/distributor as fallbacks).
 */
function priceFor(p, variation = null) {
    if (variation) {
        const wholesale = Number(p.wholesale_price ?? 0);
        const additional = Number(variation.additional_price ?? 0);
        const multiplier = Math.max(1, Number(variation.quantity_multiplier ?? 1));
        return wholesale * multiplier + additional;
    }
    return Number(p.wholesale_price ?? p.retail_price ?? p.distributor_price ?? 0);
}

function formatCurrency(value) {
    const n = Number(value) || 0;
    return '$' + n.toFixed(2);
}

function customerTypeVariant(type) {
    return ({ retail: 'info', wholesale: 'brand', distributor: 'warning', vip: 'danger' }[type]) || 'neutral';
}

function onSearchInput() {
    clearTimeout(searchDebounce);
    if (!search.value.trim()) {
        searchResults.value = [];
        return;
    }
    searchDebounce = setTimeout(runSearch, 250);
}

async function runSearch() {
    const q = search.value.trim();
    if (!q) {
        searchResults.value = [];
        return;
    }
    searching.value = true;
    try {
        const res = await api.get('/products', {
            params: { q, per_page: 20, status: 'active' },
        });
        searchResults.value = res.data?.data || res.data || [];
    } catch (e) {
        searchResults.value = [];
    } finally {
        searching.value = false;
    }
}

async function onSearchEnter() {
    clearTimeout(searchDebounce);
    await runSearch();
    // Auto-add only if there is exactly one sellable item
    const flat = flattenResults(searchResults.value);
    if (flat.length === 1) {
        addProduct(flat[0].product, flat[0].variation);
    }
}

/**
 * Flatten the search results into a list of clickable items. A product
 * without variations yields one item; a product with variations yields
 * one item per active variation.
 */
function flattenResults(products) {
    const items = [];
    for (const p of products) {
        const vars = (p.variations || []).filter((v) => v.is_active !== false);
        if (vars.length === 0) {
            items.push({ product: p, variation: null });
        } else {
            for (const v of vars) {
                items.push({ product: p, variation: v });
            }
        }
    }
    return items;
}

function addProduct(p, variation = null) {
    if (!p || (p.total_stock !== undefined && Number(p.total_stock) <= 0)) {
        toast.warning(`${p?.name || 'Product'} is out of stock.`);
        return;
    }
    cart.addLine(p, variation, null, 1, null);
    search.value = '';
    searchResults.value = [];
    nextTick(() => searchInputRef.value?.focus());
}

function onCustomerSearch() {
    clearTimeout(customerDebounce);
    customerDebounce = setTimeout(async () => {
        const q = customerSearch.value.trim();
        if (!q) {
            customerResults.value = [];
            return;
        }
        try {
            const res = await api.get('/customers', {
                params: { q, per_page: 8 },
            });
            customerResults.value = res.data?.data || res.data || [];
            customerResultsOpen.value = true;
        } catch (e) {
            customerResults.value = [];
        }
    }, 200);
}

function selectCustomer(c) {
    cart.setCustomer(c);
    customerSearch.value = '';
    customerResults.value = [];
    customerResultsOpen.value = false;
}

function closeCustomerResults() {
    setTimeout(() => (customerResultsOpen.value = false), 150);
}

function quickAddPayment(method) {
    const remaining = cart.remainingToPay.value;
    if (method === 'credit' && !cart.customer.value) {
        toast.warning('Select a customer before recording a credit payment.');
        return;
    }
    const amount = remaining > 0 ? remaining : 0;
    cart.addPayment(method, amount, '');
}

function updatePaymentAmount(idx, value) {
    const amount = Math.max(0, Number(value) || 0);
    const next = cart.payments.value.slice();
    next[idx] = { ...next[idx], amount };
    cart.setPayments(next);
}

const canSubmit = computed(() => {
    if (cart.isEmpty.value) return false;
    if (!warehouseId.value) return false;
    if (cart.payments.value.length === 0) return false;
    if (cart.paid.value <= 0) return false;
    return true;
});

async function completeSale() {
    errorBanner.value = '';
    cart.setWarehouse(warehouses.value.find((w) => String(w.id) === String(warehouseId.value)));
    const payload = cart.buildPayload();

    submitting.value = true;
    try {
        const sale = await salesStore.create(payload);
        completedSale.value = sale;
    } catch (e) {
        const data = e.response?.data;
        let msg = data?.message || 'Could not complete sale.';
        if (data?.errors) {
            const flat = Object.values(data.errors).flat();
            if (flat.length) msg = flat.join(' ');
        }
        errorBanner.value = msg;
        nextTick(() => searchInputRef.value?.focus());
    } finally {
        submitting.value = false;
    }
}

function onReceiptClose() {
    completedSale.value = null;
}
function onReceiptNew() {
    completedSale.value = null;
    cart.clear();
    nextTick(() => searchInputRef.value?.focus());
}

watch(warehouseId, (val) => {
    const w = warehouses.value.find((x) => String(x.id) === String(val));
    if (w) cart.setWarehouse(w);
});

onMounted(async () => {
    try {
        await warehousesStore.fetch();
        if (warehouses.value.length > 0) {
            warehouseId.value = String(warehouses.value[0].id);
            cart.setWarehouse(warehouses.value[0]);
        }
    } catch (e) {
        // ignore
    }
    nextTick(() => searchInputRef.value?.focus());
});
</script>
