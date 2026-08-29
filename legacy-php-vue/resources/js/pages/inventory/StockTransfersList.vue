<template>
    <InventoryHistoryList
        eyebrow="Inventory · Transfers"
        title="Stock transfers"
        subtitle="Review warehouse-to-warehouse stock moves and their audit trail."
        endpoint="/stock-transfers"
        :create-route="{ name: 'inventory.transfers.create' }"
        create-label="New transfer"
        empty-title="No transfers recorded"
        empty-description="Stock transfers between warehouses will appear here once recorded."
        :columns="columns"
    />
</template>

<script setup>
import InventoryHistoryList from '@/pages/inventory/InventoryHistoryList.vue';

const columns = [
    { key: 'reference_number', label: 'Reference', sortable: true },
    { key: 'source_warehouse', label: 'Source', format: (value) => value?.name || '—' },
    { key: 'destination_warehouse', label: 'Destination', format: (value) => value?.name || '—' },
    { key: 'item_count', label: 'Items', format: (value) => value ?? '—' },
    { key: 'transferred_at', label: 'Transferred at', format: (value) => value ? new Date(value).toLocaleString() : '—' },
    { key: 'status', label: 'Status' },
];
</script>
