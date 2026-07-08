<template>
  <BaseModal v-model="isOpen" title="Select Batch &amp; Unit">
    <div v-if="!product" class="text-center text-sm text-slate-500 py-8">
      No product selected.
    </div>

    <div v-else class="space-y-4">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-slate-50 flex items-center justify-center shrink-0">
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
          </svg>
        </div>
        <div class="min-w-0">
          <p class="text-sm font-medium text-slate-900 truncate">{{ product.name }}</p>
          <p class="text-xs text-slate-500">
            {{ product.sku || '—' }}
            <span v-if="variationLabel"> · {{ variationLabel }}</span>
          </p>
        </div>
      </div>

      <!-- Batches -->
      <div v-if="trackStock && batches.length > 0">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Batches (FEFO)</p>
        <div class="space-y-1 max-h-40 overflow-y-auto">
          <label
            v-for="batch in batches"
            :key="batch.id"
            class="flex items-center gap-3 px-3 py-2 rounded-lg border text-sm cursor-pointer transition-colors"
            :class="selectedBatchId === batch.id ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-slate-200 hover:bg-slate-50'"
          >
            <input
              v-model="selectedBatchId"
              type="radio"
              name="batch"
              :value="batch.id"
              class="sr-only"
            />
            <span class="font-medium">{{ batch.batch_number }}</span>
            <span class="ml-auto text-xs" :class="batch.remaining_quantity <= 10 ? 'text-rose-600 font-semibold' : 'text-slate-500'">
              {{ batch.remaining_quantity }} in stock
            </span>
            <span v-if="batch.expiry_date" class="text-xs" :class="expiryClass(batch.expiry_date)">
              Exp. {{ formatDate(batch.expiry_date) }}
            </span>
          </label>
        </div>
      </div>

      <div v-else-if="trackStock && batches.length === 0" class="rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-700">
        No active batches available for this product in the selected warehouse.
      </div>

      <!-- Units -->
      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Unit</p>
        <div class="flex flex-wrap gap-2">
          <label
            v-for="u in units"
            :key="u.id"
            class="px-3 py-1.5 rounded-md border text-sm cursor-pointer transition-colors"
            :class="selectedUnitId === u.id ? 'border-brand-500 bg-brand-50 text-brand-700 font-medium' : 'border-slate-200 hover:bg-slate-50'"
          >
            <input
              v-model="selectedUnitId"
              type="radio"
              name="unit"
              :value="u.id"
              class="sr-only"
            />
            {{ u.name }}
            <span v-if="u.short_code" class="text-xs text-slate-400">({{ u.short_code }})</span>
          </label>
        </div>
      </div>

      <!-- Quantity -->
      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Quantity</p>
        <div class="flex items-center rounded-md border border-slate-300 w-fit">
          <button type="button" class="px-3 py-1.5 text-slate-500 hover:text-slate-700" @click="quantity = Math.max(1, quantity - 1)">−</button>
          <input
            v-model.number="quantity"
            type="number"
            min="1"
            class="w-14 text-center text-sm border-x border-slate-300 py-1.5 focus:outline-none"
          />
          <button type="button" class="px-3 py-1.5 text-slate-500 hover:text-slate-700" @click="quantity++">+</button>
        </div>
      </div>
    </div>

    <template #footer>
      <button
        type="button"
        class="px-4 py-2 text-sm font-medium rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors"
        @click="isOpen = false"
      >
        Cancel
      </button>
      <button
        type="button"
        class="px-4 py-2 text-sm font-medium rounded-lg bg-brand-600 text-white hover:bg-brand-700 transition-colors"
        :disabled="!canAdd"
        @click="confirm"
      >
        Add to Cart
      </button>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import BaseModal from '@/components/ui/BaseModal.vue';

const isOpen = defineModel({ type: Boolean, default: false });

const props = defineProps({
  product: { type: Object, default: null },
  variation: { type: Object, default: null },
  batches: { type: Array, default: () => [] },
  units: { type: Array, default: () => [] },
  trackStock: { type: Boolean, default: true },
});

const emit = defineEmits(['confirm']);

const selectedBatchId = ref(null);
const selectedUnitId = ref(null);
const quantity = ref(1);

const variationLabel = computed(() => props.variation?.value ?? null);

const canAdd = computed(() => {
  if (!props.product) return false;
  if (props.trackStock && props.batches.length > 0 && !selectedBatchId.value) return false;
  if (!selectedUnitId.value) return false;
  if (quantity.value < 1) return false;
  return true;
});

function formatDate(date) {
  if (!date) return '';
  const d = new Date(date);
  return d.toLocaleDateString();
}

function expiryClass(date) {
  const days = Math.ceil((new Date(date) - new Date()) / (1000 * 60 * 60 * 24));
  if (days <= 7) return 'text-rose-600 font-medium';
  if (days <= 30) return 'text-amber-600';
  return 'text-slate-500';
}

function confirm() {
  const selectedBatch = props.batches.find((b) => b.id === selectedBatchId.value);
  const selectedUnit = props.units.find((u) => u.id === selectedUnitId.value);

  emit('confirm', {
    batchId: selectedBatchId.value,
    batchNumber: selectedBatch?.batch_number ?? null,
    unitId: selectedUnitId.value,
    unitName: selectedUnit?.name ?? '',
    unitShortCode: selectedUnit?.short_code ?? '',
    quantity: quantity.value,
  });
  isOpen.value = false;
}

watch(isOpen, (open) => {
  if (open) {
    selectedBatchId.value = props.batches[0]?.id ?? null;
    selectedUnitId.value = props.units[0]?.id ?? null;
    quantity.value = 1;
  }
});
</script>
