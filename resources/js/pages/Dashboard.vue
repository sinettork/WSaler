<template>
    <div class="space-y-6">
        <div v-if="loading" class="flex justify-center py-24">
            <Spinner size="lg" label="Loading dashboard..." />
        </div>

        <template v-else>
            <PageHeader
                :title="`Welcome back, ${auth.user?.name || ''}`"
                :subtitle="today"
            />

            <!-- Stat cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <BaseCard v-for="stat in stats" :key="stat.label">
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

            <!-- Quick actions + recent activity -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <BaseCard class="lg:col-span-1">
                    <template #header>
                        <h2 class="text-sm font-semibold text-slate-900">Quick actions</h2>
                    </template>
                    <div class="space-y-2">
                        <router-link
                            v-for="action in quickActions"
                            :key="action.to"
                            :to="action.to"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-50 transition-colors group"
                        >
                            <div class="shrink-0 w-9 h-9 rounded-lg flex items-center justify-center" :class="action.iconBg">
                                <svg class="w-4 h-4" :class="action.iconClass" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="action.iconPath" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-900">{{ action.label }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ action.description }}</p>
                            </div>
                            <svg class="w-4 h-4 text-slate-300 group-hover:text-slate-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </router-link>
                    </div>
                </BaseCard>

                <BaseCard class="lg:col-span-2" padding="none">
                    <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900">Recent batches</h2>
                            <p class="mt-0.5 text-xs text-slate-500">Latest stock receipts</p>
                        </div>
                        <router-link
                            to="/batches"
                            class="text-xs font-medium text-brand-600 hover:text-brand-700"
                        >
                            View all →
                        </router-link>
                    </div>
                    <EmptyState
                        v-if="recentBatches.length === 0"
                        icon="inbox"
                        title="No recent batches"
                        description="Received batches will appear here as your team records them."
                    >
                        <template #actions>
                            <BaseButton
                                v-if="auth.hasRole(['admin','manager','warehouse','purchasing'])"
                                size="sm"
                                @click="$router.push('/batches')"
                            >
                                Record a batch
                            </BaseButton>
                        </template>
                    </EmptyState>
                    <ul v-else class="divide-y divide-slate-100">
                        <li
                            v-for="b in recentBatches"
                            :key="b.id"
                            class="px-5 py-3 flex items-center gap-3 text-sm"
                        >
                            <div class="shrink-0 w-9 h-9 rounded-lg bg-slate-50 flex items-center justify-center">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-slate-900 truncate">{{ b.product?.name || '—' }}</p>
                                <p class="text-xs text-slate-500 truncate">
                                    {{ b.batch_number }}
                                    <span v-if="b.warehouse"> · {{ b.warehouse.name }}</span>
                                </p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="font-semibold text-slate-900">{{ b.remaining_quantity }}<span class="text-slate-400 font-normal"> / {{ b.quantity }}</span></p>
                                <BaseBadge v-if="b.expiry_status" :variant="expiryVariant(b.expiry_status)" class="mt-1">
                                    {{ b.expiry_date ? new Date(b.expiry_date).toLocaleDateString() : '—' }}
                                </BaseBadge>
                            </div>
                        </li>
                    </ul>
                </BaseCard>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useBatchesStore } from '@/stores/batches';
import { useProductsStore } from '@/stores/products';
import BaseCard from '@/components/ui/BaseCard.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseBadge from '@/components/ui/BaseBadge.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import Spinner from '@/components/ui/Spinner.vue';

const auth = useAuthStore();
const batchesStore = useBatchesStore();
const productsStore = useProductsStore();

const loading = ref(true);
const recentBatches = ref([]);
const lowStockCount = ref(0);
const expiringSoonCount = ref(0);

const today = new Date().toLocaleDateString(undefined, {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
});

const stats = computed(() => [
    {
        label: "Today's Sales",
        value: '$0.00',
        delta: 'No sales yet today',
        deltaClass: 'text-slate-500',
        iconBg: 'bg-emerald-50',
        iconClass: 'text-emerald-600',
        iconPath: 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
    },
    {
        label: 'Monthly Revenue',
        value: '$0.00',
        delta: 'No revenue this month',
        deltaClass: 'text-slate-500',
        iconBg: 'bg-brand-50',
        iconClass: 'text-brand-600',
        iconPath: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        label: 'Low Stock',
        value: `${lowStockCount.value} item${lowStockCount.value === 1 ? '' : 's'}`,
        delta: lowStockCount.value === 0 ? 'All products well stocked' : `${lowStockCount.value} product${lowStockCount.value === 1 ? '' : 's'} below threshold`,
        deltaClass: lowStockCount.value === 0 ? 'text-slate-500' : 'text-amber-600',
        iconBg: 'bg-amber-50',
        iconClass: 'text-amber-600',
        iconPath: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    },
    {
        label: 'Near Expiry',
        value: `${expiringSoonCount.value} item${expiringSoonCount.value === 1 ? '' : 's'}`,
        delta: expiringSoonCount.value === 0 ? 'No batches expiring soon' : `${expiringSoonCount.value} batch${expiringSoonCount.value === 1 ? '' : 'es'} within 30 days`,
        deltaClass: expiringSoonCount.value === 0 ? 'text-slate-500' : 'text-rose-600',
        iconBg: 'bg-rose-50',
        iconClass: 'text-rose-600',
        iconPath: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    },
]);

const quickActions = [
    {
        to: '/products/new',
        label: 'Add product',
        description: 'Create a new product in the catalog',
        iconBg: 'bg-brand-50',
        iconClass: 'text-brand-600',
        iconPath: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    },
    {
        to: '/batches',
        label: 'Receive batch',
        description: 'Log a new stock batch with expiry',
        iconBg: 'bg-emerald-50',
        iconClass: 'text-emerald-600',
        iconPath: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        to: '/master/customers',
        label: 'Add customer',
        description: 'Add a new customer to your directory',
        iconBg: 'bg-sky-50',
        iconClass: 'text-sky-600',
        iconPath: 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
    },
    {
        to: '/products',
        label: 'View inventory',
        description: 'Browse and manage your products',
        iconBg: 'bg-amber-50',
        iconClass: 'text-amber-600',
        iconPath: 'M4 6h16M4 10h16M4 14h16M4 18h16',
    },
];

function expiryVariant(status) {
    switch (status) {
        case 'expired':
        case 'critical':
            return 'danger';
        case 'warning':
            return 'warning';
        case 'notice':
            return 'info';
        case 'good':
            return 'success';
        default:
            return 'neutral';
    }
}

onMounted(async () => {
    try {
        const [batchesRes, expiringRes, productsRes] = await Promise.all([
            batchesStore.fetch({ per_page: 5 }),
            batchesStore.fetchExpiring().catch(() => []),
            productsStore.fetch({ per_page: 200 }).catch(() => ({ items: [] })),
        ]);

        recentBatches.value = batchesRes?.items || batchesStore.items.slice(0, 5) || [];
        expiringSoonCount.value = Array.isArray(expiringRes) ? expiringRes.length : (expiringRes?.items?.length || 0);
        const products = productsRes?.items || productsStore.items || [];
        lowStockCount.value = products.filter(p => (p.total_stock ?? 0) <= (p.low_stock_threshold ?? 0)).length;
    } finally {
        loading.value = false;
    }
});
</script>
