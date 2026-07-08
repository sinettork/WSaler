<template>
    <div class="space-y-6">
        <PageHeader :eyebrow="eyebrow" :title="title" :subtitle="subtitle">
            <template #actions>
                <BaseButton variant="secondary" @click="$router.push('/inventory/operations')">
                    Back to operations
                </BaseButton>
                <BaseButton @click="goToCreate">
                    {{ createLabel }}
                </BaseButton>
            </template>
        </PageHeader>

        <BaseCard v-if="$slots.filters" padding="md">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <slot name="filters" />
            </div>
        </BaseCard>

        <BaseCard padding="none">
            <DataTable
                :items="items"
                :columns="columns"
                :loading="loading"
                @search="onSearch"
                @page-change="onPage"
            >
                <template v-for="col in columns" :key="col.key" #[`cell-${col.key}`]="slot">
                    <slot :name="`cell-${col.key}`" :value="slot.value" :row="slot.row">
                        <BaseBadge v-if="col.key === 'status'" :variant="statusVariant(slot.value)">
                            {{ slot.value || '—' }}
                        </BaseBadge>
                        <span v-else>{{ formatCellValue(slot.value, col) }}</span>
                    </slot>
                </template>
                <template #empty>
                    <EmptyState
                        icon="inbox"
                        :title="emptyTitle"
                        :description="emptyDescription"
                    >
                        <template #actions>
                            <BaseButton size="sm" @click="goToCreate">{{ createLabel }}</BaseButton>
                        </template>
                    </EmptyState>
                </template>
            </DataTable>
        </BaseCard>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useApi } from '@/composables/useApi';
import PageHeader from '@/components/ui/PageHeader.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import BaseBadge from '@/components/ui/BaseBadge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import DataTable from '@/components/DataTable.vue';

const props = defineProps({
    eyebrow: { type: String, default: 'Inventory' },
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    endpoint: { type: String, required: true },
    createRoute: { type: Object, default: () => ({ name: 'inventory.operations' }) },
    createLabel: { type: String, default: 'New operation' },
    columns: { type: Array, required: true },
    emptyTitle: { type: String, default: 'No records yet' },
    emptyDescription: { type: String, default: 'Records will appear here as your team adds them.' },
});

const router = useRouter();
const api = useApi();
const items = ref([]);
const loading = ref(false);
const searchQuery = ref('');

async function fetchData(page = 1) {
    loading.value = true;
    try {
        const res = await api.get(props.endpoint, {
            params: {
                search: searchQuery.value,
                page,
            },
        });
        const payload = res.data?.data ?? res.data;
        items.value = payload?.data || payload || [];
    } catch (error) {
        items.value = [];
    } finally {
        loading.value = false;
    }
}

function onSearch(query) {
    searchQuery.value = query;
    fetchData(1);
}

function onPage(page) {
    fetchData(page);
}

function goToCreate() {
    router.push(props.createRoute);
}

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

function formatCellValue(value, col) {
    if (col.format) return col.format(value);
    if (value == null) return '—';
    return value;
}

defineExpose({ refresh: fetchData, statusVariant });

onMounted(() => {
    fetchData();
});
</script>
