<template>
    <InventoryOperationForm
        title="Stock transfers"
        subtitle="Move stock between warehouses while preserving a transfer audit trail."
        header="Transfer details"
        :submit-action="submit"
        @submitted="onSubmitted"
    >
        <template #fields>
            <BaseSelect
                v-model="form.source_warehouse_id"
                name="source_warehouse_id"
                label="Source warehouse"
                placeholder="Select source warehouse"
                :options="warehouseOptions"
            />
            <BaseSelect
                v-model="form.destination_warehouse_id"
                name="destination_warehouse_id"
                label="Destination warehouse"
                placeholder="Select destination warehouse"
                :options="warehouseOptions"
            />
            <BaseInput v-model="form.reference_number" label="Reference number" placeholder="TR-001" />
            <BaseInput v-model="form.notes" label="Notes" placeholder="Optional" />
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
import { useWarehousesStore } from '@/stores/warehouses';

const router = useRouter();
const api = useApi();
const warehouseStore = useWarehousesStore();
const warehouseOptions = ref([]);
const form = reactive({
    source_warehouse_id: '',
    destination_warehouse_id: '',
    reference_number: '',
    notes: '',
});

async function submit(items) {
    await api.post('/stock-transfers', {
        ...form,
        items: items.filter((item) => item.product_id),
    });
}

function onSubmitted() {
    router.push('/inventory/operations');
}

onMounted(async () => {
    await warehouseStore.fetch();
    warehouseOptions.value = warehouseStore.active.map((warehouse) => ({ value: warehouse.id, label: warehouse.name }));
});
</script>
