<template>
    <InventoryHistoryList
        eyebrow="Inventory · Adjustments"
        title="Stock adjustments"
        subtitle="Review corrections and inventory changes made to maintain accuracy."
        endpoint="/stock-adjustments"
        :create-route="{ name: 'inventory.adjustments.create' }"
        create-label="New adjustment"
        empty-title="No adjustments recorded"
        empty-description="Stock corrections and adjustments will appear here once recorded."
        :columns="columns"
    />
</template>

<script setup>
import InventoryHistoryList from '@/pages/inventory/InventoryHistoryList.vue';

const columns = [
    { key: 'reference_number', label: 'Reference', sortable: true },
    { key: 'warehouse', label: 'Warehouse', format: (value) => value?.name || '—' },
    { key: 'reason', label: 'Reason' },
    { key: 'item_count', label: 'Items', format: (value) => value ?? '—' },
    { key: 'adjusted_at', label: 'Adjusted at', format: (value) => value ? new Date(value).toLocaleString() : '—' },
    { key: 'status', label: 'Status' },
];
</script>
