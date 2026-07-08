<template>
    <InventoryHistoryList
        eyebrow="Inventory · Refunds"
        title="Refunds"
        subtitle="Review returns and refund activity that restored stock to inventory."
        endpoint="/refunds"
        :create-route="{ name: 'inventory.refunds.create' }"
        create-label="New refund"
        empty-title="No refunds recorded"
        empty-description="Customer returns and refunds will appear here once recorded."
        :columns="columns"
    />
</template>

<script setup>
import InventoryHistoryList from '@/pages/inventory/InventoryHistoryList.vue';

const columns = [
    { key: 'reference_number', label: 'Reference', sortable: true },
    { key: 'customer', label: 'Customer', format: (value) => value?.name || '—' },
    { key: 'warehouse', label: 'Warehouse', format: (value) => value?.name || '—' },
    { key: 'item_count', label: 'Items', format: (value) => value ?? '—' },
    { key: 'refund_amount', label: 'Amount', format: (value) => value != null ? Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—' },
    { key: 'refunded_at', label: 'Refunded at', format: (value) => value ? new Date(value).toLocaleString() : '—' },
    { key: 'status', label: 'Status' },
];
</script>
