<template>
  <Transition
    enter-active-class="transition ease-out duration-200"
    enter-from-class="translate-x-full"
    enter-to-class="translate-x-0"
    leave-active-class="transition ease-in duration-150"
    leave-from-class="translate-x-0"
    leave-to-class="translate-x-full"
  >
    <div v-show="isOpen" class="fixed inset-y-0 right-0 z-40 w-80 bg-white shadow-2xl border-l border-slate-200 flex flex-col">
      <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-slate-900">Held Orders</h3>
        <button type="button" class="text-slate-400 hover:text-slate-600" aria-label="Close" @click="close">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <div class="flex-1 overflow-y-auto p-3 space-y-2">
        <div v-if="loading" class="flex justify-center py-8">
          <Spinner size="sm" />
        </div>

        <div v-else-if="drafts.length === 0" class="text-center py-8">
          <div class="w-12 h-12 rounded-full bg-slate-50 flex items-center justify-center mx-auto mb-3">
            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
          </div>
          <p class="text-sm text-slate-500 font-medium">No held orders</p>
          <p class="text-xs text-slate-400 mt-1">Press Hold Cart to save a draft.</p>
        </div>

        <template v-else>
          <div
            v-for="draft in drafts"
            :key="draft.id"
            class="rounded-lg border border-slate-200 p-3 hover:border-brand-300 hover:shadow-sm transition-all group"
          >
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-slate-900 truncate">{{ draft.name || `Draft #${draft.id}` }}</p>
                <p class="text-xs text-slate-500 mt-0.5">
                  {{ formatDate(draft.updated_at) }}
                  · {{ draft.items?.length || 0 }} item(s)
                  · {{ formatMoney(draft.total) }}
                </p>
                <p v-if="draft.customer" class="text-xs text-slate-500 mt-0.5 truncate">
                  {{ draft.customer.name }}
                </p>
              </div>
              <div class="flex flex-col gap-1">
                <button
                  type="button"
                  class="text-xs px-2 py-1 rounded border border-brand-200 bg-brand-50 text-brand-700 font-medium hover:bg-brand-100 transition-colors"
                  @click="recall(draft)"
                >
                  Recall
                </button>
                <button
                  type="button"
                  class="text-xs px-2 py-1 rounded text-rose-600 hover:text-rose-700 hover:bg-rose-50 transition-colors opacity-0 group-hover:opacity-100"
                  @click="deleteDraft(draft.id)"
                >
                  Delete
                </button>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>
  </Transition>

  <!-- Backdrop -->
  <Transition
    enter-active-class="transition-opacity ease-out duration-200"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition-opacity ease-in duration-150"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div v-show="isOpen" class="fixed inset-0 z-30 bg-slate-900/30" @click="close" />
  </Transition>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import Spinner from '@/components/ui/Spinner.vue';
import { useApi } from '@/composables/useApi';
import { useToastStore } from '@/stores/toast';

const api = useApi();
const toast = useToastStore();

const isOpen = defineModel({ type: Boolean, default: false });

const emit = defineEmits(['recall']);

const drafts = ref([]);
const loading = ref(false);

function formatMoney(value) {
  const n = Number(value) || 0;
  return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDate(dateStr) {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

async function load() {
  loading.value = true;
  try {
    const res = await api.get('/draft-orders');
    drafts.value = res.data?.data || [];
  } catch {
    drafts.value = [];
  } finally {
    loading.value = false;
  }
}

async function deleteDraft(id) {
  try {
    await api.delete(`/draft-orders/${id}`);
    drafts.value = drafts.value.filter((d) => d.id !== id);
    toast.success('Draft deleted.');
  } catch {
    toast.error('Failed to delete draft.');
  }
}

function recall(draft) {
  emit('recall', draft);
  close();
}

function close() {
  isOpen.value = false;
}

watch(isOpen, (open) => {
  if (open) load();
});

onMounted(() => {
  // Load drafts silently in background
  load();
});
</script>
