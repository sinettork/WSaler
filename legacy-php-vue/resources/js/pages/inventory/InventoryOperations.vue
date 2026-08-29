<template>
    <div class="space-y-6">
        <PageHeader
            eyebrow="Inventory"
            title="Inventory operations"
            subtitle="Record receipts, refunds, stock transfers, and adjustments in the same workflow style."
        >
            <template #actions>
                <BaseButton variant="secondary" @click="$router.push({ name: 'inventory.receipts.index' })">
                    View receipts
                </BaseButton>
                <BaseButton variant="secondary" @click="$router.push({ name: 'inventory.refunds.index' })">
                    View refunds
                </BaseButton>
                <BaseButton variant="secondary" @click="$router.push({ name: 'inventory.transfers.index' })">
                    View transfers
                </BaseButton>
                <BaseButton variant="secondary" @click="$router.push({ name: 'inventory.adjustments.index' })">
                    View adjustments
                </BaseButton>
            </template>
        </PageHeader>

        <!-- Stat cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <BaseCard v-for="stat in stats" :key="stat.key">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-500">{{ stat.label }}</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900">{{ stat.value }}</p>
                        <p class="mt-1 text-xs" :class="stat.deltaClass">{{ stat.delta }}</p>
                    </div>
                    <div class="shrink-0 w-10 h-10 rounded-lg flex items-center justify-center" :class="stat.iconBg">
                        <svg class="w-5 h-5" :class="stat.iconClass" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="stat.iconPath" />
                        </svg>
                    </div>
                </div>
            </BaseCard>
        </div>

        <!-- Quick actions -->
        <BaseCard>
            <template #header>
                <h2 class="text-sm font-semibold text-slate-900">Quick actions</h2>
            </template>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                <router-link
                    v-for="card in cards"
                    :key="card.key"
                    :to="card.route"
                    class="group flex items-start gap-3 rounded-lg border border-slate-200 p-4 hover:border-brand-300 hover:bg-brand-50/40 transition-colors"
                >
                    <div class="shrink-0 w-10 h-10 rounded-lg flex items-center justify-center" :class="card.iconBg">
                        <svg class="w-5 h-5" :class="card.iconClass" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="card.iconPath" />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-900">{{ card.title }}</p>
                        <p class="mt-1 text-xs text-slate-500 line-clamp-2">{{ card.description }}</p>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 group-hover:text-brand-600 transition-colors shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </router-link>
            </div>
        </BaseCard>

        <!-- Recent activity -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <BaseCard padding="none">
                <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Recent purchase receipts</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Latest stock received from suppliers</p>
                    </div>
                    <router-link :to="{ name: 'inventory.receipts.index' }" class="text-xs font-medium text-brand-600 hover:text-brand-700">
                        View all →
                    </router-link>
                </div>
                <EmptyState
                    v-if="!store.receipts.length"
                    icon="inbox"
                    title="No receipts yet"
                    description="Purchase receipts will appear here once your team records them."
                >
                    <template #actions>
                        <BaseButton size="sm" @click="$router.push({ name: 'inventory.receipts.create' })">
                            Record a receipt
                        </BaseButton>
                    </template>
                </EmptyState>
                <ul v-else class="divide-y divide-slate-100">
                    <li
                        v-for="item in store.receipts.slice(0, 5)"
                        :key="item.id"
                        class="px-5 py-3 flex items-center gap-3 text-sm hover:bg-slate-50 transition-colors"
                    >
                        <div class="shrink-0 w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-slate-900 truncate">{{ item.reference_number || `Receipt #${item.id}` }}</p>
                            <p class="text-xs text-slate-500 truncate">
                                {{ item.supplier?.name || '—' }}
                                <span v-if="item.warehouse"> · {{ item.warehouse.name }}</span>
                            </p>
                        </div>
                        <div class="text-right shrink-0">
                            <BaseBadge :variant="statusVariant(item.status)">{{ item.status || 'completed' }}</BaseBadge>
                            <p class="mt-1 text-xs text-slate-500">{{ formatDate(item.received_at) }}</p>
                        </div>
                    </li>
                </ul>
            </BaseCard>

            <BaseCard padding="none">
                <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Recent activity</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Latest refunds, transfers, and adjustments</p>
                    </div>
                </div>
                <EmptyState
                    v-if="!recentActivity.length"
                    icon="inbox"
                    title="No activity yet"
                    description="Refunds, transfers, and adjustments will appear here as they happen."
                />
                <ul v-else class="divide-y divide-slate-100">
                    <li
                        v-for="item in recentActivity"
                        :key="`${item.type}-${item.id}`"
                        class="px-5 py-3 flex items-center gap-3 text-sm hover:bg-slate-50 transition-colors"
                    >
                        <div class="shrink-0 w-9 h-9 rounded-lg flex items-center justify-center" :class="item.iconBg">
                            <svg class="w-4 h-4" :class="item.iconClass" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.iconPath" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-slate-900 truncate">{{ item.label }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ item.subtitle }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <BaseBadge :variant="statusVariant(item.status)">{{ item.status || item.type }}</BaseBadge>
                            <p class="mt-1 text-xs text-slate-500">{{ formatDate(item.date) }}</p>
                        </div>
                    </li>
                </ul>
            </BaseCard>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useInventoryStore } from '@/stores/inventory';
import PageHeader from '@/components/ui/PageHeader.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import BaseBadge from '@/components/ui/BaseBadge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';

const store = useInventoryStore();

const cards = [
    {
        key: 'receipts',
        title: 'Purchase receiving',
        description: 'Register supplier deliveries and add stock to the selected warehouse.',
        route: { name: 'inventory.receipts.create' },
        iconBg: 'bg-emerald-50',
        iconClass: 'text-emerald-600',
        iconPath: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        key: 'refunds',
        title: 'Returns & refunds',
        description: 'Record customer returns and restore stock to the appropriate location.',
        route: { name: 'inventory.refunds.create' },
        iconBg: 'bg-amber-50',
        iconClass: 'text-amber-600',
        iconPath: 'M3 10h18M7 15h10M7 5h10',
    },
    {
        key: 'transfers',
        title: 'Stock transfers',
        description: 'Move stock between warehouses with a clear transfer record.',
        route: { name: 'inventory.transfers.create' },
        iconBg: 'bg-sky-50',
        iconClass: 'text-sky-600',
        iconPath: 'M8 7h8m0 0l-4-4m4 4l-4 4m4 6H8m0 0l4 4m-4-4l4-4',
    },
    {
        key: 'adjustments',
        title: 'Stock adjustments',
        description: 'Correct stock levels for damage, shrinkage, or other inventory changes.',
        route: { name: 'inventory.adjustments.create' },
        iconBg: 'bg-rose-50',
        iconClass: 'text-rose-600',
        iconPath: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.5-9.5a2.2 2.2 0 113.4 3.4L12 15l-4 1 1-4 8.5-8.5z',
    },
];

const stats = computed(() => [
    {
        key: 'receipts',
        label: 'Receipts',
        value: store.receipts.length,
        delta: store.receipts.length === 0 ? 'No receipts yet' : `${store.receipts.length} recorded`,
        deltaClass: store.receipts.length === 0 ? 'text-slate-500' : 'text-emerald-600',
        iconBg: 'bg-emerald-50',
        iconClass: 'text-emerald-600',
        iconPath: 'M12 4v16m8-8H4',
    },
    {
        key: 'refunds',
        label: 'Refunds',
        value: store.refunds.length,
        delta: store.refunds.length === 0 ? 'No refunds yet' : `${store.refunds.length} processed`,
        deltaClass: store.refunds.length === 0 ? 'text-slate-500' : 'text-amber-600',
        iconBg: 'bg-amber-50',
        iconClass: 'text-amber-600',
        iconPath: 'M3 10h18M7 15h10M7 5h10',
    },
    {
        key: 'transfers',
        label: 'Transfers',
        value: store.transfers.length,
        delta: store.transfers.length === 0 ? 'No transfers yet' : `${store.transfers.length} recorded`,
        deltaClass: store.transfers.length === 0 ? 'text-slate-500' : 'text-sky-600',
        iconBg: 'bg-sky-50',
        iconClass: 'text-sky-600',
        iconPath: 'M8 7h8m0 0l-4-4m4 4l-4 4m4 6H8m0 0l4 4m-4-4l4-4',
    },
    {
        key: 'adjustments',
        label: 'Adjustments',
        value: store.adjustments.length,
        delta: store.adjustments.length === 0 ? 'No adjustments yet' : `${store.adjustments.length} applied`,
        deltaClass: store.adjustments.length === 0 ? 'text-slate-500' : 'text-rose-600',
        iconBg: 'bg-rose-50',
        iconClass: 'text-rose-600',
        iconPath: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.5-9.5a2.2 2.2 0 113.4 3.4L12 15l-4 1 1-4 8.5-8.5z',
    },
]);

const activityMeta = {
    refund: {
        iconBg: 'bg-amber-50',
        iconClass: 'text-amber-600',
        iconPath: 'M3 10h18M7 15h10M7 5h10',
    },
    transfer: {
        iconBg: 'bg-sky-50',
        iconClass: 'text-sky-600',
        iconPath: 'M8 7h8m0 0l-4-4m4 4l-4 4m4 6H8m0 0l4 4m-4-4l4-4',
    },
    adjustment: {
        iconBg: 'bg-rose-50',
        iconClass: 'text-rose-600',
        iconPath: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.5-9.5a2.2 2.2 0 113.4 3.4L12 15l-4 1 1-4 8.5-8.5z',
    },
};

const recentActivity = computed(() => {
    return [
        ...store.refunds.slice(0, 2).map((item) => ({
            id: item.id,
            type: 'refund',
            label: item.reference_number || `Refund #${item.id}`,
            subtitle: [item.customer?.name, item.warehouse?.name].filter(Boolean).join(' · ') || '—',
            date: item.refunded_at,
            status: item.status,
            ...activityMeta.refund,
        })),
        ...store.transfers.slice(0, 2).map((item) => ({
            id: item.id,
            type: 'transfer',
            label: item.reference_number || `Transfer #${item.id}`,
            subtitle: [item.from_warehouse?.name, item.to_warehouse?.name].filter(Boolean).join(' → ') || '—',
            date: item.transferred_at,
            status: item.status,
            ...activityMeta.transfer,
        })),
        ...store.adjustments.slice(0, 2).map((item) => ({
            id: item.id,
            type: 'adjustment',
            label: item.reference_number || `Adjustment #${item.id}`,
            subtitle: [item.warehouse?.name, item.reason].filter(Boolean).join(' · ') || '—',
            date: item.adjusted_at,
            status: item.status,
            ...activityMeta.adjustment,
        })),
    ].sort((a, b) => new Date(b.date || 0) - new Date(a.date || 0)).slice(0, 6);
});

function statusVariant(status) {
    switch ((status || '').toLowerCase()) {
        case 'completed':
        case 'received':
        case 'approved':
            return 'success';
        case 'pending':
        case 'in_progress':
        case 'draft':
            return 'warning';
        case 'rejected':
        case 'cancelled':
        case 'canceled':
        case 'voided':
            return 'danger';
        default:
            return 'neutral';
    }
}

function formatDate(value) {
    if (!value) return '—';
    return new Date(value).toLocaleDateString();
}

onMounted(() => {
    store.fetchOverview();
});
</script>
