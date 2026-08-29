<template>
  <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
    <div v-if="searchable" class="px-4 py-3 border-b border-slate-200">
      <div class="relative max-w-sm">
        <svg
          class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          aria-hidden="true"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" />
        </svg>
        <input
          v-model="searchQuery"
          type="search"
          placeholder="Search..."
          class="block w-full rounded-md border border-slate-300 bg-white pl-9 pr-3 py-1.5 text-[13px] shadow-sm placeholder-slate-400 font-mono focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500/30"
          @input="onSearchInput"
        />
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th
              v-for="col in columns"
              :key="col.key"
              scope="col"
              :class="['px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600', col.sortable ? 'cursor-pointer select-none hover:text-slate-900' : '']"
              @click="col.sortable ? toggleSort(col.key) : null"
            >
              <span class="inline-flex items-center gap-1">
                {{ col.label }}
                <span v-if="sortKey === col.key" class="text-slate-400">
                  {{ sortDir === 'asc' ? '\u25B2' : '\u25BC' }}
                </span>
              </span>
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 bg-white">
          <template v-if="loading">
            <tr>
              <td :colspan="columns.length" class="px-5 py-10 text-center">
                <div class="inline-flex items-center gap-2 text-sm text-slate-500">
                  <svg class="h-5 w-5 animate-spin text-brand-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                  </svg>
                  Loading...
                </div>
              </td>
            </tr>
          </template>
          <template v-else-if="!items || items.length === 0">
            <tr>
              <td :colspan="columns.length" class="px-5 py-10 text-center text-sm text-slate-500">
                No records found.
              </td>
            </tr>
          </template>
          <template v-else>
            <tr
              v-for="row in paginatedItems"
              :key="row.id"
              class="cursor-pointer hover:bg-slate-50 transition-colors"
              @click="$emit('row-click', row)"
            >
              <td
                v-for="col in columns"
                :key="col.key"
                :class="['px-4 py-2.5 text-[13px] text-slate-700 whitespace-nowrap', col.class]"
              >
                <slot :name="`cell-${col.key}`" :value="row[col.key]" :row="row">
                  {{ formatCell(row[col.key], col.format) }}
                </slot>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <nav v-if="paginated && totalPages > 1" class="px-5 py-3 border-t border-slate-200 flex items-center justify-between" aria-label="Pagination">
      <p class="text-sm text-slate-500">
        Page <span class="font-medium text-slate-700">{{ currentPage }}</span> of <span class="font-medium text-slate-700">{{ totalPages }}</span>
      </p>
      <ul class="inline-flex items-center gap-1">
        <li>
          <button
            type="button"
            :disabled="currentPage <= 1"
            class="px-3 py-1.5 text-sm rounded-md border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
            @click="goToPage(currentPage - 1)"
          >
            Previous
          </button>
        </li>
        <li v-for="p in pageNumbers" :key="p">
          <button
            type="button"
            :class="['px-3 py-1.5 text-sm rounded-md border', p === currentPage ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50']"
            @click="goToPage(p)"
          >
            {{ p }}
          </button>
        </li>
        <li>
          <button
            type="button"
            :disabled="currentPage >= totalPages"
            class="px-3 py-1.5 text-sm rounded-md border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
            @click="goToPage(currentPage + 1)"
          >
            Next
          </button>
        </li>
      </ul>
    </nav>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  items: { type: Array, default: () => [] },
  columns: { type: Array, required: true },
  searchable: { type: Boolean, default: true },
  paginated: { type: Boolean, default: true },
  loading: { type: Boolean, default: false },
  pageSize: { type: Number, default: 15 },
});

const emit = defineEmits(['search', 'sort', 'page-change', 'row-click']);

const searchQuery = ref('');
const sortKey = ref('');
const sortDir = ref('asc');
const currentPage = ref(1);

let searchTimeout = null;
function onSearchInput() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    currentPage.value = 1;
    emit('search', searchQuery.value);
  }, 300);
}

function toggleSort(key) {
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortKey.value = key;
    sortDir.value = 'asc';
  }
  emit('sort', { key: sortKey.value, direction: sortDir.value });
}

const filteredItems = computed(() => {
  let list = [...props.items];
  if (searchQuery.value && !props.searchable) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter(item => props.columns.some(col => String(item[col.key] || '').toLowerCase().includes(q)));
  }
  if (sortKey.value) {
    list.sort((a, b) => {
      const av = String(a[sortKey.value] || '').toLowerCase();
      const bv = String(b[sortKey.value] || '').toLowerCase();
      return sortDir.value === 'asc' ? av.localeCompare(bv) : bv.localeCompare(av);
    });
  }
  return list;
});

const totalPages = computed(() => Math.ceil(filteredItems.value.length / props.pageSize));
const paginatedItems = computed(() => {
  if (!props.paginated) return filteredItems.value;
  const start = (currentPage.value - 1) * props.pageSize;
  return filteredItems.value.slice(start, start + props.pageSize);
});

const pageNumbers = computed(() => {
  const pages = [];
  for (let i = 1; i <= totalPages.value; i++) pages.push(i);
  return pages;
});

function goToPage(p) {
  if (p < 1 || p > totalPages.value) return;
  currentPage.value = p;
  emit('page-change', p);
}

watch(() => props.items, () => { currentPage.value = 1; });

function formatCell(value, format) {
  if (format === 'date' && value) return new Date(value).toLocaleDateString();
  if (value == null) return '-';
  return value;
}
</script>
