<template>
    <div class="space-y-6">
        <PageHeader title="Users" subtitle="Manage user accounts and roles.">
            <template #actions>
                <BaseButton v-if="auth.hasRole('admin')" @click="$router.push({ name: 'admin.users.create' })">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add User
                </BaseButton>
            </template>
        </PageHeader>

        <BaseCard padding="md">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <BaseInput
                    v-model="filters.search"
                    name="search"
                    label="Search"
                    placeholder="Search by name or email"
                    @input="onFilter"
                />
                <BaseSelect
                    v-model="filters.role"
                    name="role"
                    label="Role"
                    placeholder="All Roles"
                    :options="[
                        { value: '', label: 'All Roles' },
                        { value: 'admin', label: 'Administrator' },
                        { value: 'manager', label: 'Manager' },
                        { value: 'cashier', label: 'Cashier' },
                        { value: 'warehouse', label: 'Warehouse Staff' },
                        { value: 'purchasing', label: 'Purchasing Staff' },
                        { value: 'delivery', label: 'Delivery Staff' },
                    ]"
                    @change="onFilter"
                />
            </div>
        </BaseCard>

        <DataTable
            :items="store.items"
            :columns="columns"
            :loading="store.loading"
            @search="onSearch"
            @sort="onSort"
            @page-change="onPage"
        >
            <template #cell-role="{ value }">
                <BaseBadge :variant="roleVariant(value)">{{ value }}</BaseBadge>
            </template>
            <template #cell-created_at="{ row }">
                {{ formatDate(row.created_at) }}
            </template>
            <template #cell-actions="{ row }">
                <div class="flex items-center gap-2">
                    <BaseButton v-if="auth.hasRole('admin')" size="sm" variant="secondary" @click.stop="$router.push({ name: 'admin.users.edit', params: { id: row.id } })">Edit</BaseButton>
                    <BaseButton v-if="auth.hasRole('admin')" size="sm" variant="danger" @click.stop="confirmDelete(row)">Delete</BaseButton>
                </div>
            </template>
        </DataTable>

        <ConfirmDialog
            :show="!!deleteTarget"
            :title="`Remove user account: ${deleteTarget?.name ?? ''}?`"
            :message="`This will remove the login for \u201C${deleteTarget?.name ?? ''}\u201D (${deleteTarget?.email ?? ''}).`"
            impact="The user will be signed out and unable to log in again. Activity history is preserved. This action cannot be undone."
            confirm-label="Remove user"
            cancel-label="Keep user"
            :loading="store.loading"
            @confirm="performDelete"
            @cancel="deleteTarget = null"
        />
    </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useUsersStore } from '@/stores/users';
import { useToastStore } from '@/stores/toast';
import DataTable from '@/components/DataTable.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseBadge from '@/components/ui/BaseBadge.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';

const auth = useAuthStore();
const store = useUsersStore();
const toast = useToastStore();

const columns = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'email', label: 'Email', sortable: true },
    { key: 'role', label: 'Role' },
    { key: 'created_at', label: 'Created' },
    { key: 'actions', label: 'Actions' },
];

const filters = reactive({ search: '', role: '' });
const deleteTarget = ref(null);

let filterTimeout = null;

function roleVariant(role) {
    const map = {
        admin: 'danger',
        manager: 'brand',
        cashier: 'success',
        warehouse: 'warning',
        purchasing: 'info',
        delivery: 'neutral',
    };
    return map[role] || 'neutral';
}

function formatDate(date) {
    return date ? new Date(date).toLocaleDateString() : '-';
}

function confirmDelete(row) {
    deleteTarget.value = row;
}

async function performDelete() {
    if (!deleteTarget.value) return;
    try {
        await store.delete(deleteTarget.value.id);
        toast.success('User account removed.');
        deleteTarget.value = null;
    } catch (e) {
        // surfaced by api interceptor
    }
}

function onFilter() {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => store.fetch(filters), 300);
}

function onSearch(q) { store.fetch({ ...filters, search: q }); }
function onSort(s) { store.fetch({ ...filters, sort: s.key, direction: s.direction }); }
function onPage(p) { store.fetch({ ...filters, page: p }); }

onMounted(() => store.fetch());
</script>
