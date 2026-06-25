<template>
    <div class="space-y-6">
        <PageHeader
            title="Units of Measure"
            subtitle="Define base and derived units used across products."
        >
            <template #actions>
                <BaseButton
                    v-if="auth.hasRole(['admin','manager'])"
                    @click="$router.push({ name: 'units.create' })"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Unit
                </BaseButton>
            </template>
        </PageHeader>

        <DataTable
            :items="store.items"
            :columns="columns"
            :loading="store.loading"
            @search="onSearch"
            @sort="onSort"
            @page-change="onPage"
        >
            <template #cell-base="{ value }">
                <BaseBadge :variant="value ? 'success' : 'neutral'">
                    {{ value ? 'Base' : 'Derived' }}
                </BaseBadge>
            </template>
            <template #cell-actions="{ row }">
                <div class="flex items-center gap-2">
                    <BaseButton
                        v-if="auth.hasRole(['admin','manager'])"
                        size="sm"
                        variant="secondary"
                        @click.stop="$router.push({ name: 'units.edit', params: { id: row.id } })"
                    >
                        Edit
                    </BaseButton>
                    <BaseButton
                        v-if="auth.hasRole('admin')"
                        size="sm"
                        variant="danger"
                        @click.stop="confirmDelete(row)"
                    >
                        Delete
                    </BaseButton>
                </div>
            </template>
        </DataTable>

        <ConfirmDialog
            :show="!!deleteTarget"
            :title="`Remove unit: ${deleteTarget?.name ?? ''}?`"
            :message="`This will remove the unit \u201C${deleteTarget?.name ?? ''}\u201D.`"
            impact="Products using this unit as their base unit will need to be reassigned. This action cannot be undone."
            confirm-label="Remove unit"
            cancel-label="Keep unit"
            :loading="store.loading"
            @confirm="performDelete"
            @cancel="deleteTarget = null"
        />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useUnitsStore } from '@/stores/units';
import { useToastStore } from '@/stores/toast';
import DataTable from '@/components/DataTable.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseBadge from '@/components/ui/BaseBadge.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';

const auth = useAuthStore();
const store = useUnitsStore();
const toast = useToastStore();

const columns = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'short_code', label: 'Short Code', sortable: true },
    { key: 'base', label: 'Type' },
    { key: 'conversion_factor_to_base', label: 'Conversion Factor', sortable: true },
    { key: 'actions', label: 'Actions' },
];

const deleteTarget = ref(null);

function confirmDelete(row) {
    deleteTarget.value = row;
}

async function performDelete() {
    if (!deleteTarget.value) return;
    try {
        await store.delete(deleteTarget.value.id);
        toast.success('Unit removed.');
        deleteTarget.value = null;
    } catch (e) {
        // surfaced by api interceptor
    }
}

function onSearch(q) { store.fetch(); }
function onSort() { store.fetch(); }
function onPage() { store.fetch(); }

onMounted(() => store.fetch());
</script>
