<template>
  <aside class="w-full lg:w-56 bg-white rounded-xl border border-slate-200 flex flex-col max-h-full">
    <div class="px-4 py-3 border-b border-slate-200">
      <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Categories</h3>
    </div>

    <div v-if="loading" class="p-4">
      <Spinner size="sm" :center="false" label="Loading categories…" />
    </div>

    <div v-else-if="categories.length === 0" class="p-4 text-sm text-slate-500">
      No categories available.
    </div>

    <div v-else class="flex-1 overflow-y-auto p-2 space-y-0.5">
      <!-- Flat category list with indentation based on parent_id -->
      <button
        v-for="cat in flattenedCategories"
        :key="cat.id"
        type="button"
        class="w-full text-left px-3 py-2 rounded-lg text-sm transition-colors flex items-center justify-between gap-2"
        :class="selectedId === cat.id ? 'bg-brand-50 text-brand-700 font-medium' : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900'"
        :style="{ paddingLeft: `${0.75 + cat.depth * 0.75}rem` }"
        @click="selectCategory(cat.id)"
      >
        <div class="flex items-center gap-1.5 min-w-0">
          <svg v-if="cat.depth > 0" class="w-3 h-3 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
          </svg>
          <svg v-else class="w-3.5 h-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
          </svg>
          <span class="truncate">{{ cat.name }}</span>
        </div>
        <span
          class="text-xs shrink-0 px-1.5 py-0.5 rounded-full font-medium"
          :class="selectedId === cat.id ? 'bg-brand-100 text-brand-700' : 'bg-slate-100 text-slate-600'"
        >
          {{ cat.products_count }}
        </span>
      </button>
    </div>
  </aside>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useApi } from '@/composables/useApi';
import Spinner from '@/components/ui/Spinner.vue';

const api = useApi();

const categories = ref([]);
const loading = ref(false);
const selectedId = defineModel('selectedId', { type: [Number, String], default: null });

const emit = defineEmits(['select']);

const flattenedCategories = computed(() => {
  const cats = categories.value;
  if (!cats?.length) return [];

  // Build depth map
  const byParent = {};
  for (const c of cats) {
    const pid = c.parent_id ?? 0;
    if (!byParent[pid]) byParent[pid] = [];
    byParent[pid].push(c);
  }

  const result = [];
  function walk(parentId, depth) {
    const children = byParent[parentId] || [];
    for (const child of children) {
      result.push({ ...child, depth });
      walk(child.id, depth + 1);
    }
  }
  walk(0, 0);
  return result;
});

function selectCategory(id) {
  selectedId.value = selectedId.value === id ? null : id;
  emit('select', selectedId.value);
}

async function load() {
  loading.value = true;
  try {
    const res = await api.get('/categories/tree');
    categories.value = res.data?.data || [];
  } catch {
    categories.value = [];
  } finally {
    loading.value = false;
  }
}

onMounted(load);
</script>
