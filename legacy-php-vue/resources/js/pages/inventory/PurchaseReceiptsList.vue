<template>
    <InventoryHistoryList
        eyebrow="Inventory · Receipts"
        title="Purchase receipts"
        subtitle="Review supplier receipts and the stock they added to each warehouse."
        endpoint="/purchase-receipts"
        :create-route="{ name: 'inventory.receipts.create' }"
        create-label="New receipt"
        empty-title="No receipts recorded"
        empty-description="Record supplier deliveries to add stock and keep inventory accurate."
        :columns="columns"
    />
</template>

<script setup>
import InventoryHistoryList from '@/pages/inventory/InventoryHistoryList.vue';
import BaseBadge from '@/components/ui/BaseBadge.vue';

const columns = [
    { key: 'reference_number', label: 'Reference', sortable: true },
    { key: 'supplier', label: 'Supplier', format: (value) => value?.name || '—' },
    { key: 'warehouse', label: 'Warehouse', format: (value) => value?.name || '—' },
    { key: 'item_count', label: 'Items', format: (value) => value ?? '—' },
    { key: 'total_cost', label: 'Total cost', format: (value) => value != null ? Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—' },
    { key: 'received_at', label: 'Received at', format: (value) => value ? new Date(value).toLocaleString() : '—' },
    { key: 'status', label: 'Status' },
];
</script>
