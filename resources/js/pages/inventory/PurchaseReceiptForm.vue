<template>
    <InventoryOperationForm
        title="Purchase receiving"
        subtitle="Record incoming stock from suppliers and increase inventory for the selected warehouse."
        header="Receipt details"
        :show-cost="true"
        :submit-action="submit"
        @submitted="onSubmitted"
    >
        <template #fields>
            <BaseSelect
                v-model="form.supplier_id"
                name="supplier_id"
                label="Supplier"
                placeholder="Select supplier"
                :options="supplierOptions"
            />
            <BaseSelect
                v-model="form.warehouse_id"
                name="warehouse_id"
                label="Warehouse"
                placeholder="Select warehouse"
                :options="warehouseOptions"
            />
            <BaseInput v-model="form.reference_number" label="Reference number" placeholder="PO-1001" />
            <BaseInput v-model="form.received_at" type="datetime-local" label="Received at" />
            <BaseInput v-model="form.notes" label="Notes" placeholder="Optional notes" />
        </template>
    </InventoryOperationForm>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import InventoryOperationForm from '@/pages/inventory/InventoryOperationForm.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import { useApi } from '@/composables/useApi';
import { useSuppliersStore } from '@/stores/suppliers';
import { useWarehousesStore } from '@/stores/warehouses';

const router = useRouter();
const api = useApi();
const supplierStore = useSuppliersStore();
const warehouseStore = useWarehousesStore();

const supplierOptions = ref([]);
const warehouseOptions = ref([]);
const form = reactive({
    supplier_id: '',
    warehouse_id: '',
    reference_number: '',
    received_at: new Date().toISOString().slice(0, 16),
    notes: '',
});

async function submit(items) {
    await api.post('/purchase-receipts', {
        ...form,
        items: items.filter((item) => item.product_id),
    });
}

function onSubmitted() {
    router.push('/inventory/operations');
}

onMounted(async () => {
    await Promise.all([supplierStore.fetch(), warehouseStore.fetch()]);
    supplierOptions.value = supplierStore.active.map((s) => ({ value: s.id, label: s.name }));
    warehouseOptions.value = warehouseStore.active.map((w) => ({ value: w.id, label: w.name }));
});
</script>
