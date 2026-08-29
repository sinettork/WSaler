<template>
    <div class="space-y-6">
        <form novalidate class="space-y-6" @submit.prevent="submit">
            <PageHeader :eyebrow="eyebrow" :title="title" :subtitle="subtitle">
                <template #actions>
                    <BaseButton type="button" variant="secondary" @click="$router.push('/inventory/operations')">
                        Back to operations
                    </BaseButton>
                    <BaseButton type="submit" :loading="saving">
                        {{ submitLabel }}
                    </BaseButton>
                </template>
            </PageHeader>

            <BaseCard>
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900">{{ header }}</h2>
                </template>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <slot name="fields" />
                </div>
            </BaseCard>

            <BaseCard padding="none">
                <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Items</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Add one row per product affected by this operation.</p>
                    </div>
                    <BaseButton size="sm" variant="secondary" type="button" @click="addRow">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add item
                    </BaseButton>
                </div>

                <div v-if="!rows.length" class="p-5">
                    <EmptyState
                        icon="box"
                        title="No items yet"
                        description="Add at least one product line to record this operation."
                    >
                        <template #actions>
                            <BaseButton size="sm" @click="addRow">Add first item</BaseButton>
                        </template>
                    </EmptyState>
                </div>

                <ul v-else class="divide-y divide-slate-100">
                    <li
                        v-for="(row, index) in rows"
                        :key="index"
                        class="p-4 grid grid-cols-1 lg:grid-cols-12 gap-3"
                    >
                        <div class="lg:col-span-5">
                            <BaseSelect
                                v-model="row.product_id"
                                :name="`items.${index}.product_id`"
                                label="Product"
                                placeholder="Select product"
                                :options="productOptions.map(p => ({ value: p.id, label: p.name }))"
                                :error="rowErrors[index]?.product_id"
                                @change="clearRowError(index, 'product_id')"
                            />
                        </div>

                        <div v-if="showVariation" class="lg:col-span-2">
                            <BaseInput
                                v-model="row.variation_id"
                                :name="`items.${index}.variation_id`"
                                label="Variation ID"
                                placeholder="Optional"
                            />
                        </div>

                        <div class="lg:col-span-2">
                            <BaseInput
                                v-model.number="row.quantity"
                                :name="`items.${index}.quantity`"
                                type="number"
                                label="Quantity"
                                min="1"
                                required
                                :error="rowErrors[index]?.quantity"
                                @input="clearRowError(index, 'quantity')"
                            />
                        </div>

                        <div v-if="showCost" class="lg:col-span-2">
                            <BaseInput
                                v-model.number="row.unit_cost"
                                :name="`items.${index}.unit_cost`"
                                type="number"
                                label="Unit cost"
                                placeholder="0.00"
                                step="0.01"
                                :error="rowErrors[index]?.unit_cost"
                                @input="clearRowError(index, 'unit_cost')"
                            />
                        </div>

                        <div v-if="showReason" class="lg:col-span-2">
                            <BaseSelect
                                v-model="row.type"
                                :name="`items.${index}.type`"
                                label="Adjustment"
                                :options="[
                                    { value: 'increase', label: 'Increase' },
                                    { value: 'decrease', label: 'Decrease' },
                                ]"
                            />
                        </div>

                        <div class="lg:col-span-1 flex lg:items-end lg:justify-end">
                            <BaseButton
                                type="button"
                                variant="ghost"
                                size="sm"
                                :disabled="rows.length === 1"
                                @click="removeRow(index)"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/>
                                </svg>
                                <span class="sr-only">Remove item</span>
                            </BaseButton>
                        </div>
                    </li>
                </ul>
            </BaseCard>

            <BaseCard>
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900">Notes</h2>
                </template>
                <textarea
                    v-model="notes"
                    rows="3"
                  class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:outline-none focus:ring-2 focus:border-brand-500 focus:ring-brand-500"
                    placeholder="Optional notes about this operation"
                />
            </BaseCard>

            <div class="flex items-center justify-end gap-2 pt-2">
                <BaseButton type="button" variant="secondary" @click="$router.push('/inventory/operations')">Cancel</BaseButton>
                <BaseButton type="submit" :loading="saving">{{ submitLabel }}</BaseButton>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import { useProductsStore } from '@/stores/products';
import { useToastStore } from '@/stores/toast';

const props = defineProps({
    eyebrow: { type: String, default: 'Inventory' },
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    header: { type: String, default: 'Details' },
    submitLabel: { type: String, default: 'Save' },
    showVariation: { type: Boolean, default: false },
    showCost: { type: Boolean, default: false },
    showReason: { type: Boolean, default: false },
    submitAction: { type: Function, required: true },
});

const emit = defineEmits(['submitted']);
const productStore = useProductsStore();
const toast = useToastStore();
const productOptions = ref([]);
const rows = ref([{ product_id: '', quantity: 1, unit_cost: 0, type: 'decrease', variation_id: '' }]);
const rowErrors = reactive({});
const notes = ref('');
const saving = ref(false);

async function loadProducts() {
    productOptions.value = await productStore.lookup('');
}

function addRow() {
    rows.value.push({ product_id: '', quantity: 1, unit_cost: 0, type: 'decrease', variation_id: '' });
}

function removeRow(index) {
    if (rows.value.length === 1) return;
    rows.value.splice(index, 1);
    delete rowErrors[index];
}

function clearRowError(index, field) {
    if (rowErrors[index] && rowErrors[index][field]) {
        delete rowErrors[index][field];
        if (!Object.keys(rowErrors[index]).length) delete rowErrors[index];
    }
}

async function submit() {
    saving.value = true;
    Object.keys(rowErrors).forEach((k) => delete rowErrors[k]);
    try {
        await props.submitAction({ items: rows.value, notes: notes.value });
        toast.success('Operation saved.');
        emit('submitted');
    } catch (e) {
        if (e?.fieldErrors?.items) {
            e.fieldErrors.items.forEach((err, idx) => {
                rowErrors[idx] = { ...(rowErrors[idx] || {}), ...err };
            });
            toast.error('Please fix the highlighted fields.');
        } else {
            toast.error('Could not save this operation.');
        }
    } finally {
        saving.value = false;
    }
}

onMounted(() => {
    loadProducts();
});
</script>
