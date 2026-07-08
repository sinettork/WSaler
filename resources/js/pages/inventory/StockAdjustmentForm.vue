<template>
    <InventoryOperationForm
        title="Stock adjustments"
        subtitle="Adjust stock for damage, correction, or other inventory changes."
        header="Adjustment details"
        :show-reason="true"
        :submit-action="submit"
        @submitted="onSubmitted"
    >
        <template #fields>
            <BaseSelect
                v-model="form.warehouse_id"
                name="warehouse_id"
                label="Warehouse"
                placeholder="Select warehouse"
                :options="warehouseOptions"
            />
            <BaseInput v-model="form.reason" label="Reason" placeholder="Damaged stock" />
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
    warehouse_id: '',
    reason: '',
});

async function submit(items) {
    await api.post('/stock-adjustments', {
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
