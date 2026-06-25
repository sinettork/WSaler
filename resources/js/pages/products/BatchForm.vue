<template>
    <div class="space-y-6">
        <div v-if="store.loading && editing" class="flex justify-center py-16">
            <Spinner size="lg" label="Loading batch..." />
        </div>

        <form v-else @submit.prevent="save" novalidate class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div class="min-w-0">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-2"
                        @click="$router.push('/batches')"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Back to batches
                    </button>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                        {{ editing ? `Update batch: ${form.batch_number || ''}` : 'Receive stock batch' }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ editing
                            ? 'Change quantities, dates, or supplier for this batch.'
                            : 'Log a new batch of stock received for a product. Enter quantity and dates to track expiry.' }}
                    </p>
                </div>
            </div>

            <BaseCard>
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900">Product &amp; location</h2>
                </template>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <BaseSelect
                        v-model="form.product_id"
                        name="product_id"
                        label="Product"
                        placeholder="Select product"
                        :options="productOptions.map(p => ({ value: p.id, label: p.name }))"
                        required
                        :error="errors.product_id"
                        @change="onProductChange"
                    />
                    <BaseSelect
                        v-model="form.variation_id"
                        name="variation_id"
                        label="Variation"
                        placeholder="None"
                        :options="[{ value: '', label: 'None' }, ...variationOptions.map(v => ({ value: v.id, label: v.value }))]"
                        :error="errors.variation_id"
                    />
                    <BaseSelect
                        v-model="form.warehouse_id"
                        name="warehouse_id"
                        label="Warehouse"
                        placeholder="Select warehouse"
                        :options="warehouseStore.active.map(w => ({ value: w.id, label: w.name }))"
                        required
                        :error="errors.warehouse_id"
                    />
                    <BaseSelect
                        v-model="form.supplier_id"
                        name="supplier_id"
                        label="Supplier"
                        placeholder="None"
                        :options="[{ value: '', label: 'None' }, ...supplierStore.active.map(s => ({ value: s.id, label: s.name }))]"
                        :error="errors.supplier_id"
                    />
                </div>
            </BaseCard>

            <BaseCard>
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900">Quantity &amp; cost</h2>
                </template>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <BaseInput
                        v-model.number="form.quantity"
                        name="quantity"
                        type="number"
                        label="Quantity"
                        required
                        :error="errors.quantity"
                        @input="clearError('quantity')"
                    />
                    <BaseInput
                        v-model.number="form.purchase_cost"
                        name="purchase_cost"
                        type="number"
                        label="Purchase cost (total)"
                        placeholder="0.00"
                        :error="errors.purchase_cost"
                        @input="clearError('purchase_cost')"
                    />
                </div>
            </BaseCard>

            <BaseCard>
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900">Dates</h2>
                </template>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <BaseInput
                        v-model="form.manufacture_date"
                        name="manufacture_date"
                        type="date"
                        label="Manufacture date"
                        :error="errors.manufacture_date"
                        @input="clearError('manufacture_date')"
                    />
                    <BaseInput
                        v-model="form.expiry_date"
                        name="expiry_date"
                        type="date"
                        label="Expiry date"
                        :error="errors.expiry_date"
                        @input="clearError('expiry_date')"
                    />
                    <BaseInput
                        v-model="form.received_date"
                        name="received_date"
                        type="date"
                        label="Received date"
                        required
                        :error="errors.received_date"
                        @input="clearError('received_date')"
                    />
                </div>
            </BaseCard>

            <BaseCard>
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900">Notes</h2>
                </template>
                <label for="batch-notes" class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                <textarea
                    id="batch-notes"
                    v-model="form.notes"
                    name="notes"
                    rows="2"
                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:outline-none focus:ring-2 focus:border-brand-500 focus:ring-brand-500"
                    placeholder="Optional notes"
                />
            </BaseCard>

            <div class="flex items-center justify-end gap-2 pt-2">
                <BaseButton type="button" variant="secondary" @click="$router.push('/batches')">
                    Cancel
                </BaseButton>
                <BaseButton type="submit" :loading="store.loading">
                    {{ editing ? 'Save changes' : 'Receive batch' }}
                </BaseButton>
            </div>
        </form>
    </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useBatchesStore } from '@/stores/batches';
import { useWarehousesStore } from '@/stores/warehouses';
import { useSuppliersStore } from '@/stores/suppliers';
import { useProductsStore } from '@/stores/products';
import { useToastStore } from '@/stores/toast';
import { useFormErrors } from '@/composables/useFormErrors';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import Spinner from '@/components/ui/Spinner.vue';

const route = useRoute();
const router = useRouter();
const store = useBatchesStore();
const warehouseStore = useWarehousesStore();
const supplierStore = useSuppliersStore();
const productStore = useProductsStore();
const toast = useToastStore();
const errors = reactive({});
const { focusFirstError, clearErrors, clearError } = useFormErrors(errors);

const editing = computed(() => !!route.params.id);

const productOptions = ref([]);
const variationOptions = ref([]);

const form = reactive({
    id: null,
    product_id: '',
    variation_id: '',
    warehouse_id: '',
    supplier_id: '',
    quantity: 1,
    purchase_cost: 0,
    manufacture_date: '',
    expiry_date: '',
    received_date: new Date().toISOString().slice(0, 10),
    notes: '',
});

async function loadProducts() {
    productOptions.value = await productStore.lookup('');
}

async function onProductChange() {
    variationOptions.value = [];
    form.variation_id = '';
    if (!form.product_id) return;
    try {
        const product = await productStore.get(form.product_id);
        variationOptions.value = product?.variations || [];
    } catch (e) {
        variationOptions.value = [];
    }
}

async function save() {
    clearErrors();
    try {
        const payload = { ...form };
        if (!payload.variation_id) delete payload.variation_id;
        if (!payload.supplier_id) delete payload.supplier_id;
        if (editing.value) {
            await store.update(form.id, payload);
            toast.success('Batch updated.');
        } else {
            delete payload.id;
            await store.create(payload);
            toast.success('Batch received.');
        }
        router.push('/batches');
    } catch (e) {
        if (e.fieldErrors) {
            Object.assign(errors, e.fieldErrors);
            focusFirstError();
        }
    }
}

onMounted(async () => {
    await Promise.all([
        warehouseStore.fetch(),
        supplierStore.fetch(),
        loadProducts(),
    ]);

    if (editing.value) {
        const row = await store.show(route.params.id);
        const data = row?.data || row;
        if (data) {
            Object.assign(form, {
                id: data.id,
                product_id: data.product_id || '',
                variation_id: data.variation_id || '',
                warehouse_id: data.warehouse_id || '',
                supplier_id: data.supplier_id || '',
                quantity: data.quantity ?? 1,
                purchase_cost: data.purchase_cost ?? 0,
                manufacture_date: data.manufacture_date ? data.manufacture_date.slice(0, 10) : '',
                expiry_date: data.expiry_date ? data.expiry_date.slice(0, 10) : '',
                received_date: data.received_date ? data.received_date.slice(0, 10) : new Date().toISOString().slice(0, 10),
                notes: data.notes || '',
            });
            if (form.product_id) await onProductChange();
        }
    }
});
</script>
