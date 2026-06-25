<template>
    <div class="space-y-6">
        <PageHeader
            title="Batches"
            subtitle="Track received batches, expiry dates and remaining stock."
        >
            <template #actions>
                <BaseButton
                    v-if="auth.hasRole(['admin','manager','warehouse','purchasing'])"
                    @click="$router.push({ name: 'batches.create' })"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Batch
                </BaseButton>
            </template>
        </PageHeader>

        <BaseCard padding="md">
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <BaseSelect
                    v-model="filters.product_id"
                    name="product_id"
                    label="Product"
                    placeholder="All"
                    :options="productOptions.map(p => ({ value: p.id, label: p.name }))"
                    @change="onFilter"
                />
                <BaseSelect
                    v-model="filters.warehouse_id"
                    name="warehouse_id"
                    label="Warehouse"
                    placeholder="All"
                    :options="warehouseStore.active.map(w => ({ value: w.id, label: w.name }))"
                    @change="onFilter"
                />
                <BaseSelect
                    v-model="filters.status"
                    name="status"
                    label="Status"
                    placeholder="All"
                    :options="[
                        { value: 'active', label: 'Active' },
                        { value: 'depleted', label: 'Depleted' },
                    ]"
                    @change="onFilter"
                />
                <BaseInput
                    v-model.number="filters.expiring_within"
                    name="expiring_within"
                    type="number"
                    label="Expiring within (days)"
                    placeholder="Any"
                    @input="onFilter"
                />
                <label class="flex items-end pb-2 cursor-pointer">
                    <input
                        v-model="filters.expired"
                        type="checkbox"
                        class="w-4 h-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                        @change="onFilter"
                    />
                    <span class="ml-2 text-sm text-slate-700">Expired only</span>
                </label>
            </div>
        </BaseCard>

        <TabNav v-model="tab" :tabs="tabs" />

        <DataTable
            :items="displayItems"
            :columns="columns"
            :loading="store.loading"
            @search="onSearch"
            @sort="onSort"
            @page-change="onPage"
        >
            <template #cell-product="{ row }">
                {{ row.product?.name || '\u2014' }}
            </template>
            <template #cell-warehouse="{ row }">
                {{ row.warehouse?.name || '\u2014' }}
            </template>
            <template #cell-quantities="{ row }">
                <span class="text-slate-700">{{ row.remaining_quantity }}</span>
                <span class="text-slate-400"> / {{ row.quantity }}</span>
            </template>
            <template #cell-expiry="{ row }">
                <BaseBadge :variant="expiryVariant(row.expiry_status)">
                    {{ row.expiry_date ? new Date(row.expiry_date).toLocaleDateString() : '\u2014' }}
                </BaseBadge>
            </template>
            <template #cell-status="{ value }">
                <BaseBadge :variant="value === 'active' ? 'success' : 'neutral'">
                    {{ value }}
                </BaseBadge>
            </template>
            <template #cell-actions="{ row }">
                <div class="flex items-center gap-2">
                    <BaseButton
                        v-if="auth.hasRole(['admin','manager','warehouse'])"
                        size="sm"
                        variant="secondary"
                        @click.stop="$router.push({ name: 'batches.edit', params: { id: row.id } })"
                    >
                        Edit
                    </BaseButton>
                    <BaseButton
                        v-if="auth.hasRole(['admin','manager'])"
                        size="sm"
                        variant="danger"
                        @click.stop="confirmDelete(row)"
                    >
                        Delete
                    </BaseButton>
                </div>
            </template>
        </DataTable>

        <ConfirmDialog
            :show="!!deleteTarget"
            :title="`Remove batch: ${deleteTarget?.batch_number ?? ''}?`"
            :message="`This will remove batch \u201C${deleteTarget?.batch_number ?? ''}\u201D.`"
            impact="Stock movements referencing this batch will lose their source. This action cannot be undone."
            confirm-label="Remove batch"
            cancel-label="Keep batch"
            :loading="store.loading"
            @confirm="performDelete"
            @cancel="deleteTarget = null"
        />
    </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useBatchesStore } from '@/stores/batches';
import { useWarehousesStore } from '@/stores/warehouses';
import { useSuppliersStore } from '@/stores/suppliers';
import { useProductsStore } from '@/stores/products';
import { useToastStore } from '@/stores/toast';
import DataTable from '@/components/DataTable.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseBadge from '@/components/ui/BaseBadge.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import TabNav from '@/components/ui/TabNav.vue';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';

const auth = useAuthStore();
const store = useBatchesStore();
const warehouseStore = useWarehousesStore();
const supplierStore = useSuppliersStore();
const productStore = useProductsStore();
const toast = useToastStore();

const columns = [
    { key: 'batch_number', label: 'Batch #', sortable: true },
    { key: 'product', label: 'Product' },
    { key: 'warehouse', label: 'Warehouse' },
    { key: 'quantity', label: 'Qty', sortable: true },
    { key: 'remaining_quantity', label: 'Remaining', sortable: true },
    { key: 'expiry', label: 'Expiry' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Actions' },
];

const tab = ref('all');
const tabs = computed(() => [
    { key: 'all', label: 'All Batches', count: store.items.length },
    { key: 'expiring', label: 'Expiring Soon', count: (store.expiring || []).length },
    { key: 'expired', label: 'Expired', count: (store.expired || []).length },
]);

const filters = reactive({
    product_id: '',
    warehouse_id: '',
    status: '',
    expiring_within: '',
    expired: false,
});

const productOptions = ref([]);
const deleteTarget = ref(null);

const displayItems = computed(() => {
    if (tab.value === 'expiring') return store.expiring || [];
    if (tab.value === 'expired') return store.expired || [];
    return store.items;
});

function expiryVariant(status) {
    switch (status) {
        case 'expired':
        case 'critical':
            return 'danger';
        case 'warning':
            return 'warning';
        case 'notice':
            return 'info';
        case 'good':
            return 'success';
        default:
            return 'neutral';
    }
}

async function loadProducts() {
    productOptions.value = await productStore.lookup('');
}

function confirmDelete(row) {
    deleteTarget.value = row;
}

async function performDelete() {
    if (!deleteTarget.value) return;
    try {
        await store.delete(deleteTarget.value.id);
        toast.success('Batch removed.');
        deleteTarget.value = null;
    } catch (e) {
        // surfaced by api interceptor
    }
}

function onSearch(q) { store.fetch({ ...filters, search: q }); }
function onSort(s) { store.fetch({ ...filters, sort: s.key, direction: s.direction }); }
function onPage(p) { store.fetch({ ...filters, page: p }); }
function onFilter() { store.fetch(filters); }

onMounted(() => {
    store.fetch();
    warehouseStore.fetch();
    supplierStore.fetch();
    loadProducts();
});

watch(tab, (val) => {
    if (val === 'expiring') store.fetchExpiring();
    if (val === 'expired') store.fetchExpired();
});
</script>
