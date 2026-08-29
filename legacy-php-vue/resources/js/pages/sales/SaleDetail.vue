<template>
    <div class="space-y-6">
        <div v-if="store.loading && !sale" class="flex justify-center py-16">
            <Spinner size="lg" label="Loading sale..." />
        </div>

        <template v-else-if="sale">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div class="min-w-0">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-2"
                        @click="$router.push({ name: 'sales.index' })"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Back to sales
                    </button>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-slate-900 tracking-tight font-mono">{{ sale.invoice_number }}</h1>
                        <BaseBadge :variant="statusVariant(sale.status)">{{ sale.status }}</BaseBadge>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">{{ formatDateTime(sale.sold_at) }}</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <BaseButton variant="secondary" @click="showReceipt = true">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Print receipt
                    </BaseButton>
                    <BaseButton
                        v-if="sale.status === 'completed' && auth.hasRole(['admin','manager'])"
                        variant="danger"
                        @click="confirmVoid"
                    >
                        Void sale
                    </BaseButton>
                </div>
            </div>

            <div v-if="sale.status === 'voided'" class="rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-800">
                <p class="font-medium">This sale was voided.</p>
                <p v-if="sale.voided_at" class="text-xs mt-1">
                    Voided on {{ formatDateTime(sale.voided_at) }}
                    <span v-if="sale.voidedBy"> by {{ sale.voidedBy.name }}</span>
                </p>
                <p v-if="sale.void_reason" class="text-xs mt-1 italic">&ldquo;{{ sale.void_reason }}&rdquo;</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <BaseCard class="lg:col-span-2" padding="none">
                    <div class="px-5 py-4 border-b border-slate-200">
                        <h2 class="text-sm font-semibold text-slate-900">Items</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Product</th>
                                    <th class="px-5 py-2.5 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Qty</th>
                                    <th class="px-5 py-2.5 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Unit</th>
                                    <th class="px-5 py-2.5 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Discount</th>
                                    <th class="px-5 py-2.5 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="item in sale.items || []" :key="item.id">
                                    <td class="px-5 py-3">
                                        <p class="text-sm font-medium text-slate-900">{{ item.product?.name || '\u2014' }}</p>
                                        <p class="text-xs text-slate-500">{{ item.product?.sku }}</p>
                                    </td>
                                    <td class="px-5 py-3 text-right text-sm text-slate-700">{{ item.quantity }}</td>
                                    <td class="px-5 py-3 text-right text-sm text-slate-700">{{ formatMoney(item.unit_price) }}</td>
                                    <td class="px-5 py-3 text-right text-sm text-slate-700">
                                        <span v-if="Number(item.discount) > 0">{{ formatMoney(item.discount) }}</span>
                                        <span v-else class="text-slate-400">&mdash;</span>
                                    </td>
                                    <td class="px-5 py-3 text-right text-sm font-semibold text-slate-900">{{ formatMoney(item.line_total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </BaseCard>

                <div class="space-y-4">
                    <BaseCard>
                        <template #header><h2 class="text-sm font-semibold text-slate-900">Summary</h2></template>
                        <dl class="space-y-2 text-sm">
                            <div v-if="sale.customer" class="flex justify-between">
                                <dt class="text-slate-500">Customer</dt>
                                <dd class="font-medium text-slate-900">{{ sale.customer.name }} <span class="text-slate-400 font-normal">({{ sale.customer.code }})</span></dd>
                            </div>
                            <div v-if="sale.warehouse" class="flex justify-between">
                                <dt class="text-slate-500">Warehouse</dt>
                                <dd class="font-medium text-slate-900">{{ sale.warehouse.name }}</dd>
                            </div>
                            <div v-if="sale.user" class="flex justify-between">
                                <dt class="text-slate-500">Cashier</dt>
                                <dd class="font-medium text-slate-900">{{ sale.user.name }}</dd>
                            </div>
                            <div class="border-t border-slate-100 pt-2 space-y-1.5">
                                <div class="flex justify-between">
                                    <dt class="text-slate-500">Subtotal</dt>
                                    <dd class="text-slate-900">{{ formatMoney(sale.subtotal) }}</dd>
                                </div>
                                <div v-if="Number(sale.discount) > 0" class="flex justify-between">
                                    <dt class="text-slate-500">Discount</dt>
                                    <dd class="text-slate-900">\u2212{{ formatMoney(sale.discount) }}</dd>
                                </div>
                                <div v-if="Number(sale.tax) > 0" class="flex justify-between">
                                    <dt class="text-slate-500">Tax</dt>
                                    <dd class="text-slate-900">{{ formatMoney(sale.tax) }}</dd>
                                </div>
                                <div class="flex justify-between pt-1.5 border-t border-slate-100">
                                    <dt class="font-semibold text-slate-900">Total</dt>
                                    <dd class="font-bold text-base text-slate-900">{{ formatMoney(sale.total) }}</dd>
                                </div>
                            </div>
                        </dl>
                    </BaseCard>

                    <BaseCard padding="md">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Payments</p>
                        <ul v-if="(sale.payments || []).length > 0" class="space-y-2">
                            <li v-for="(p, i) in sale.payments" :key="i" class="flex items-center justify-between text-sm">
                                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-xs font-medium text-slate-700 uppercase">{{ p.method.replace('_', ' ') }}</span>
                                <span class="font-medium text-slate-900">{{ formatMoney(p.amount) }}</span>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-slate-500">No payments recorded.</p>
                        <div class="mt-3 pt-3 border-t border-slate-100 space-y-1.5 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-slate-500">Paid</dt>
                                <dd class="text-slate-900 font-medium">{{ formatMoney(sale.paid) }}</dd>
                            </div>
                            <div v-if="Number(sale.change_due) > 0" class="flex justify-between">
                                <dt class="text-slate-500">Change</dt>
                                <dd class="font-semibold text-emerald-700">{{ formatMoney(sale.change_due) }}</dd>
                            </div>
                        </div>
                    </BaseCard>

                    <BaseCard v-if="sale.notes" padding="md">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Notes</p>
                        <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ sale.notes }}</p>
                    </BaseCard>
                </div>
            </div>

            <PosReceipt
                v-if="showReceipt"
                :sale="sale"
                @close="showReceipt = false"
                @new="$router.push({ name: 'sales.pos' })"
            />

            <ConfirmDialog
                :show="voidDialogOpen"
                :title="`Void sale: ${sale.invoice_number}?`"
                :message="`This will cancel the sale and restore stock for ${(sale.items || []).length} line(s).`"
                impact="Stock will be added back to the batches. If the customer paid by credit, their balance will be reduced. This action cannot be undone."
                confirm-label="Void sale"
                cancel-label="Keep sale"
                :loading="voiding"
                @confirm="performVoid"
                @cancel="voidDialogOpen = false"
            />
        </template>

        <div v-else class="bg-white border border-amber-200 rounded-xl p-6 text-sm text-amber-800">
            Sale not found.
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useSalesStore } from '@/stores/sales';
import { useToastStore } from '@/stores/toast';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import BaseBadge from '@/components/ui/BaseBadge.vue';
import Spinner from '@/components/ui/Spinner.vue';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';
import PosReceipt from './PosReceipt.vue';
import { useCurrency } from '@/composables/useCurrency';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const store = useSalesStore();
const toast = useToastStore();

const sale = computed(() => store.current);

const showReceipt = ref(false);
const voidDialogOpen = ref(false);
const voiding = ref(false);

function statusVariant(status) {
    return ({
        completed: 'success',
        voided: 'danger',
        draft: 'warning',
        refunded: 'info',
    }[status]) || 'neutral';
}



function formatDateTime(value) {
    if (!value) return '\u2014';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleString();
}

function confirmVoid() {
    voidDialogOpen.value = true;
}

async function performVoid() {
    if (!sale.value) return;
    voiding.value = true;
    try {
        const updated = await store.void(sale.value.id, '');
        toast.success(`Sale ${updated.invoice_number} voided.`);
        voidDialogOpen.value = false;
        await store.get(updated.id);
    } catch (e) {
        toast.error(e.response?.data?.message || 'Could not void sale.');
    } finally {
        voiding.value = false;
    }
}

onMounted(() => {
    store.get(route.params.id);
});
</script>
