<template>
    <InventoryOperationForm
        title="Returns and refunds"
        subtitle="Record a customer return, issue a refund, and restore stock into inventory."
        header="Refund details"
        :show-cost="true"
        :submit-action="submit"
        @submitted="onSubmitted"
    >
        <template #fields>
            <BaseInput v-model="form.sale_id" type="number" label="Sale ID" placeholder="123" />
            <BaseSelect
                v-model="form.customer_id"
                name="customer_id"
                label="Customer"
                placeholder="Select customer"
                :options="customerOptions"
            />
            <BaseSelect
                v-model="form.warehouse_id"
                name="warehouse_id"
                label="Warehouse"
                placeholder="Select warehouse"
                :options="warehouseOptions"
            />
            <BaseInput v-model="form.refund_amount" type="number" label="Refund amount" />
            <BaseInput v-model="form.reason" label="Reason" placeholder="Damaged item" />
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
import { useCustomersStore } from '@/stores/customers';
import { useWarehousesStore } from '@/stores/warehouses';

const router = useRouter();
const api = useApi();
const customerStore = useCustomersStore();
const warehouseStore = useWarehousesStore();
const customerOptions = ref([]);
const warehouseOptions = ref([]);
const form = reactive({
    sale_id: '',
    customer_id: '',
    warehouse_id: '',
    refund_amount: 0,
    reason: '',
});

async function submit(items) {
    await api.post('/refunds', {
        ...form,
        items: items.filter((item) => item.product_id),
    });
}

function onSubmitted() {
    router.push('/inventory/operations');
}

onMounted(async () => {
    await Promise.all([customerStore.fetch(), warehouseStore.fetch()]);
    customerOptions.value = customerStore.items.map((customer) => ({ value: customer.id, label: customer.name }));
    warehouseOptions.value = warehouseStore.active.map((warehouse) => ({ value: warehouse.id, label: warehouse.name }));
});
</script>
