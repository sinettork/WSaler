<template>
    <div class="space-y-6">
        <PageHeader title="Categories" subtitle="Manage product categories.">
            <template #actions>
                <BaseButton v-if="auth.hasRole(['admin','manager'])" @click="$router.push({ name: 'master.categories.create' })">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Category
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
            <template #cell-is_active="{ value }">
                <BaseBadge :variant="value ? 'success' : 'neutral'">
                    {{ value ? 'Active' : 'Inactive' }}
                </BaseBadge>
            </template>
            <template #cell-parent="{ row }">
                {{ row.parent?.name || '\u2014' }}
            </template>
            <template #cell-actions="{ row }">
                <div class="flex items-center gap-2">
                    <BaseButton v-if="auth.hasRole(['admin','manager'])" size="sm" variant="secondary" @click.stop="$router.push({ name: 'master.categories.edit', params: { id: row.id } })">Edit</BaseButton>
                    <BaseButton v-if="auth.hasRole(['admin','manager'])" size="sm" variant="danger" @click.stop="confirmDelete(row)">Delete</BaseButton>
                </div>
            </template>
        </DataTable>

        <ConfirmDialog
            :show="!!deleteTarget"
            :title="`Remove category: ${deleteTarget?.name ?? ''}?`"
            :message="`This will remove the category \u201C${deleteTarget?.name ?? ''}\u201D from your catalog.`"
            impact="Products in this category will need to be reassigned. This action cannot be undone."
            confirm-label="Remove category"
            cancel-label="Keep category"
            :loading="store.loading"
            @confirm="performDelete"
            @cancel="deleteTarget = null"
        />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useCategoriesStore } from '@/stores/categories';
import { useToastStore } from '@/stores/toast';
import DataTable from '@/components/DataTable.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseBadge from '@/components/ui/BaseBadge.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';

const auth = useAuthStore();
const store = useCategoriesStore();
const toast = useToastStore();

const columns = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'slug', label: 'Slug', sortable: true },
    { key: 'parent', label: 'Parent' },
    { key: 'is_active', label: 'Status' },
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
        toast.success('Category removed.');
        deleteTarget.value = null;
    } catch (e) {
        // surfaced by api interceptor
    }
}

function onSearch(q) { store.fetch({ search: q }); }
function onSort(s) { store.fetch({ sort: s.key, direction: s.direction }); }
function onPage(p) { store.fetch({ page: p }); }

onMounted(() => store.fetch());
</script>
