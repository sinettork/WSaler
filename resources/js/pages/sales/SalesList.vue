<template>
    <div class="space-y-6">
        <PageHeader title="Sales" subtitle="Sales history and completed transactions.">
            <template #actions>
                <BaseButton v-if="auth.hasRole(['admin','manager','cashier','warehouse'])" @click="$router.push({ name: 'sales.pos' })">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    New sale
                </BaseButton>
            </template>
        </PageHeader>

        <BaseCard padding="md">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
                <BaseInput
                    v-model="filters.from"
                    name="from"
                    type="date"
                    label="From"
                    @change="applyFilters"
                />
                <BaseInput
                    v-model="filters.to"
                    name="to"
                    type="date"
                    label="To"
                    @change="applyFilters"
                />
                <BaseSelect
                    v-model="filters.customer_id"
                    name="customer_id"
                    label="Customer"
                    placeholder="All"
                    :options="customerOptions"
                    @change="applyFilters"
                />
                <BaseSelect
                    v-model="filters.warehouse_id"
                    name="warehouse_id"
                    label="Warehouse"
                    placeholder="All"
                    :options="warehouseOptions"
                    @change="applyFilters"
                />
                <BaseSelect
                    v-model="filters.status"
                    name="status"
                    label="Status"
                    placeholder="All"
                    :options="[
                        { value: 'completed', label: 'Completed' },
                        { value: 'voided', label: 'Voided' },
                        { value: 'draft', label: 'Draft' },
                        { value: 'refunded', label: 'Refunded' },
                    ]"
                    @change="applyFilters"
                />
            </div>
            <div class="mt-3 flex items-center gap-2">
                <BaseInput
                    v-model="filters.q"
                    name="q"
                    placeholder="Search invoice number or notes\u2026"
                    @input="onSearch"
                />
                <BaseButton size="sm" variant="secondary" type="button" @click="resetFilters">Reset</BaseButton>
            </div>
        </BaseCard>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <BaseCard padding="md">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Today</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ formatCurrency(todayTotal) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ todayCount }} sale(s)</p>
            </BaseCard>
            <BaseCard padding="md">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">This week</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ formatCurrency(weekTotal) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ weekCount }} sale(s)</p>
            </BaseCard>
            <BaseCard padding="md">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Voided</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ voidedCount }}</p>
                <p class="mt-1 text-xs text-slate-500">cancelled sales</p>
            </BaseCard>
        </div>

        <DataTable
            :items="store.items"
            :columns="columns"
            :loading="store.loading"
            @page-change="onPage"
        >
            <template #cell-invoice="{ row }">
                <router-link :to="{ name: 'sales.show', params: { id: row.id } }" class="font-mono text-sm text-brand-600 hover:text-brand-700">
                    {{ row.invoice_number }}
                </router-link>
            </template>
            <template #cell-sold_at="{ row }">
                {{ formatDateTime(row.sold_at) }}
            </template>
            <template #cell-customer="{ row }">
                {{ row.customer?.name || '\u2014' }}
            </template>
            <template #cell-warehouse="{ row }">
                {{ row.warehouse?.name || '\u2014' }}
            </template>
            <template #cell-total="{ row }">
                {{ formatCurrency(row.total) }}
            </template>
            <template #cell-status="{ row }">
                <BaseBadge :variant="statusVariant(row.status)">{{ row.status }}</BaseBadge>
            </template>
            <template #cell-actions="{ row }">
                <div class="flex items-center gap-2">
                    <BaseButton size="sm" variant="secondary" @click.stop="$router.push({ name: 'sales.show', params: { id: row.id } })">View</BaseButton>
                    <BaseButton
                        v-if="row.status === 'completed' && auth.hasRole(['admin','manager'])"
                        size="sm"
                        variant="danger"
                        @click.stop="confirmVoid(row)"
                    >
                        Void
                    </BaseButton>
                </div>
            </template>
        </DataTable>

        <ConfirmDialog
            :show="!!voidTarget"
            :title="`Void sale: ${voidTarget?.invoice_number ?? ''}?`"
            :message="`This will cancel the sale and restore stock for ${voidTarget?.items?.length ?? 0} line(s).`"
            impact="Stock will be added back to the batches. If the customer paid by credit, their balance will be reduced. This action cannot be undone."
            confirm-label="Void sale"
            cancel-label="Keep sale"
            @confirm="performVoid"
            @cancel="voidTarget = null"
        />
    </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useSalesStore } from '@/stores/sales';
import { useWarehousesStore } from '@/stores/warehouses';
import { useToastStore } from '@/stores/toast';
import { useApi } from '@/composables/useApi';
import DataTable from '@/components/DataTable.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import BaseBadge from '@/components/ui/BaseBadge.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';

const auth = useAuthStore();
const store = useSalesStore();
const warehousesStore = useWarehousesStore();
const toast = useToastStore();
const api = useApi();

const columns = [
    { key: 'invoice', label: 'Invoice', sortable: false },
    { key: 'sold_at', label: 'Date', sortable: false },
    { key: 'customer', label: 'Customer' },
    { key: 'warehouse', label: 'Warehouse' },
    { key: 'total', label: 'Total', sortable: false },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Actions' },
];

const filters = reactive({
    from: '',
    to: '',
    customer_id: '',
    warehouse_id: '',
    status: '',
    q: '',
    page: 1,
});

const customerOptions = ref([]);
const warehouseOptions = computed(() =>
    (warehousesStore.items || []).map((w) => ({ value: w.id, label: w.name }))
);

const voidTarget = ref(null);

function buildParams() {
    const p = { per_page: 25 };
    if (filters.from) p.from = filters.from;
    if (filters.to) p.to = filters.to;
    if (filters.customer_id) p.customer_id = filters.customer_id;
    if (filters.warehouse_id) p.warehouse_id = filters.warehouse_id;
    if (filters.status) p.status = filters.status;
    if (filters.q) p.q = filters.q;
    if (filters.page) p.page = filters.page;
    return p;
}

let searchDebounce = null;
function onSearch() {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
        filters.page = 1;
        store.fetch(buildParams());
    }, 250);
}

function applyFilters() {
    filters.page = 1;
    store.fetch(buildParams());
}

function onPage(p) {
    filters.page = p;
    store.fetch(buildParams());
}

function resetFilters() {
    filters.from = '';
    filters.to = '';
    filters.customer_id = '';
    filters.warehouse_id = '';
    filters.status = '';
    filters.q = '';
    filters.page = 1;
    store.fetch(buildParams());
}

function statusVariant(status) {
    return ({
        completed: 'success',
        voided: 'danger',
        draft: 'warning',
        refunded: 'info',
    }[status]) || 'neutral';
}

function formatCurrency(value) {
    const n = Number(value) || 0;
    return '$' + n.toFixed(2);
}

function formatDateTime(value) {
    if (!value) return '\u2014';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleString();
}

function confirmVoid(row) {
    voidTarget.value = row;
}

async function performVoid() {
    if (!voidTarget.value) return;
    try {
        await store.void(voidTarget.value.id, '');
        toast.success(`Sale ${voidTarget.value.invoice_number} voided.`);
        voidTarget.value = null;
        store.fetch(buildParams());
    } catch (e) {
        toast.error(e.response?.data?.message || 'Could not void sale.');
    }
}

const todayTotal = computed(() => {
    const today = new Date().toISOString().slice(0, 10);
    return store.items
        .filter((s) => s.status === 'completed' && (s.sold_at || '').slice(0, 10) === today)
        .reduce((sum, s) => sum + Number(s.total || 0), 0);
});

const todayCount = computed(() => {
    const today = new Date().toISOString().slice(0, 10);
    return store.items.filter((s) => s.status === 'completed' && (s.sold_at || '').slice(0, 10) === today).length;
});

const weekTotal = computed(() => {
    const now = new Date();
    const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000).toISOString();
    return store.items
        .filter((s) => s.status === 'completed' && (s.sold_at || '') >= weekAgo)
        .reduce((sum, s) => sum + Number(s.total || 0), 0);
});

const weekCount = computed(() => {
    const now = new Date();
    const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000).toISOString();
    return store.items.filter((s) => s.status === 'completed' && (s.sold_at || '') >= weekAgo).length;
});

const voidedCount = computed(() => store.items.filter((s) => s.status === 'voided').length);

onMounted(async () => {
    try {
        await warehousesStore.fetch();
    } catch (e) {
        // ignore
    }
    try {
        const res = await api.get('/customers', { params: { per_page: 200 } });
        const list = res.data?.data || res.data || [];
        customerOptions.value = list.map((c) => ({ value: c.id, label: `${c.code} \u2014 ${c.name}` }));
    } catch (e) {
        // ignore
    }
    store.fetch(buildParams());
});
</script>
