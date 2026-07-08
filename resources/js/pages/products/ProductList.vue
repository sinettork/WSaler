<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Products</h1>
        <p class="text-sm text-slate-500 mt-1">Manage your product catalog.</p>
      </div>
      <BaseButton v-if="auth.hasRole(['admin','manager'])" @click="$router.push('/products/new')">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Product
      </BaseButton>
    </div>

    <BaseCard>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1.5">Search</label>
          <input v-model="filters.search" placeholder="Search..." class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500" @input="onFilterChange" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1.5">Category</label>
          <select v-model="filters.category_id" class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500" @change="onFilterChange">
            <option value="">All Categories</option>
            <option v-for="c in categoryStore.active" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1.5">Brand</label>
          <select v-model="filters.brand_id" class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500" @change="onFilterChange">
            <option value="">All Brands</option>
            <option v-for="b in brandStore.active" :key="b.id" :value="b.id">{{ b.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1.5">Status</label>
          <select v-model="filters.status" class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500" @change="onFilterChange">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>
    </BaseCard>

    <DataTable :items="store.items" :columns="columns" :loading="store.loading" @search="onSearch" @sort="onSort" @page-change="onPage">
      <template #cell-image="{ row }">
        <img v-if="row.image_url" :src="row.image_url" alt="product" class="w-12 h-12 object-cover rounded-md border border-slate-200" />
        <span v-else class="inline-flex w-12 h-12 items-center justify-center rounded-md border border-slate-200 text-slate-400">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        </span>
      </template>
      <template #cell-total_stock="{ value }">
        <BaseBadge :variant="value > 0 ? 'success' : 'danger'">{{ value }}</BaseBadge>
      </template>
      <template #cell-status="{ value }">
        <BaseBadge :variant="value === 'active' ? 'success' : 'neutral'">{{ value }}</BaseBadge>
      </template>
      <template #cell-retail_price="{ value }">
        {{ formatMoney(value) }}
      </template>
      <template #cell-wholesale_price="{ value }">
        {{ formatMoney(value) }}
      </template>
      <template #cell-actions="{ row }">
        <div class="flex items-center gap-2">
          <BaseButton size="sm" variant="secondary" @click.stop="$router.push('/products/' + row.id)">View</BaseButton>
          <BaseButton v-if="auth.hasRole(['admin','manager'])" size="sm" variant="secondary" @click.stop="$router.push('/products/' + row.id + '/edit')">Edit</BaseButton>
          <BaseButton v-if="auth.hasRole(['admin','manager'])" size="sm" variant="danger" @click.stop="confirmDelete(row)">Delete</BaseButton>
        </div>
      </template>
    </DataTable>
  </div>
</template>

<script setup>
import { reactive, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useProductsStore } from '@/stores/products';
import { useCategoriesStore } from '@/stores/categories';
import { useBrandsStore } from '@/stores/brands';
import { useToastStore } from '@/stores/toast';
import DataTable from '@/components/DataTable.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseBadge from '@/components/ui/BaseBadge.vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import { useCurrency } from '@/composables/useCurrency';

const auth = useAuthStore();
const { formatMoney } = useCurrency();
const store = useProductsStore();
const categoryStore = useCategoriesStore();
const brandStore = useBrandsStore();
const toast = useToastStore();

const columns = [
  { key: 'image', label: 'Image' },
  { key: 'sku', label: 'SKU', sortable: true },
  { key: 'name', label: 'Name', sortable: true },
  { key: 'category', label: 'Category' },
  { key: 'brand', label: 'Brand' },
  { key: 'retail_price', label: 'Retail' },
  { key: 'wholesale_price', label: 'Wholesale' },
  { key: 'total_stock', label: 'Stock' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: 'Actions' },
];

const filters = reactive({ search: '', category_id: '', brand_id: '', status: '' });



function confirmDelete(row) {
  if (!confirm(`Delete product "${row.name}"?`)) return;
  store.delete(row.id).then(() => toast.success('Product deleted.')).catch(() => {});
}

let filterTimeout = null;
function onFilterChange() {
  clearTimeout(filterTimeout);
  filterTimeout = setTimeout(() => store.fetch(filters), 300);
}

function onSearch(q) { store.fetch({ ...filters, search: q }); }
function onSort(s) { store.fetch({ ...filters, sort: s.key, direction: s.direction }); }
function onPage(p) { store.fetch({ ...filters, page: p }); }

onMounted(() => {
  store.fetch();
  categoryStore.fetch();
  brandStore.fetch();
});
</script>
