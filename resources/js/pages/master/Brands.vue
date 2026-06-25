<template>
    <div class="space-y-6">
        <PageHeader title="Brands" subtitle="Manage product brands.">
            <template #actions>
                <BaseButton v-if="auth.hasRole(['admin','manager'])" @click="$router.push({ name: 'master.brands.create' })">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Brand
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
            <template #cell-logo="{ row }">
                <img v-if="row.logo_url" :src="row.logo_url" alt="logo" class="h-8 object-contain" />
                <span v-else class="text-slate-400">\u2014</span>
            </template>
            <template #cell-is_active="{ value }">
                <BaseBadge :variant="value ? 'success' : 'neutral'">
                    {{ value ? 'Active' : 'Inactive' }}
                </BaseBadge>
            </template>
            <template #cell-actions="{ row }">
                <div class="flex items-center gap-2">
                    <BaseButton v-if="auth.hasRole(['admin','manager'])" size="sm" variant="secondary" @click.stop="$router.push({ name: 'master.brands.edit', params: { id: row.id } })">Edit</BaseButton>
                    <BaseButton v-if="auth.hasRole(['admin','manager'])" size="sm" variant="danger" @click.stop="confirmDelete(row)">Delete</BaseButton>
                </div>
            </template>
        </DataTable>

        <ConfirmDialog
            :show="!!deleteTarget"
            :title="`Remove brand: ${deleteTarget?.name ?? ''}?`"
            :message="`This will remove the brand \u201C${deleteTarget?.name ?? ''}\u201D.`"
            impact="Products using this brand will need to be reassigned. This action cannot be undone."
            confirm-label="Remove brand"
            cancel-label="Keep brand"
            :loading="store.loading"
            @confirm="performDelete"
            @cancel="deleteTarget = null"
        />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useBrandsStore } from '@/stores/brands';
import { useToastStore } from '@/stores/toast';
import DataTable from '@/components/DataTable.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseBadge from '@/components/ui/BaseBadge.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';

const auth = useAuthStore();
const store = useBrandsStore();
const toast = useToastStore();

const columns = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'slug', label: 'Slug', sortable: true },
    { key: 'logo', label: 'Logo' },
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
        toast.success('Brand removed.');
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
