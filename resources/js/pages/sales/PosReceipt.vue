<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 bg-slate-900/60 overflow-y-auto print:static print:bg-white print:overflow-visible" role="dialog" aria-modal="true" aria-labelledby="receipt-title">
            <div class="min-h-full flex items-start justify-center p-4 print:p-0">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md my-8 print:shadow-none print:rounded-none print:my-0 print:max-w-full">
                    <div class="p-6 receipt-print">
                        <div class="text-center mb-4">
                            <h2 id="receipt-title" class="text-lg font-bold text-slate-900">Wholesale Inventory</h2>
                            <p class="text-xs text-slate-500">Sale receipt</p>
                            <p class="text-sm font-mono mt-1">{{ sale.invoice_number }}</p>
                        </div>

                        <div class="text-xs text-slate-600 space-y-0.5 mb-4 border-t border-b border-slate-200 py-2">
                            <div class="flex justify-between">
                                <span>Date</span>
                                <span>{{ formatDateTime(sale.sold_at) }}</span>
                            </div>
                            <div v-if="sale.warehouse" class="flex justify-between">
                                <span>Warehouse</span>
                                <span>{{ sale.warehouse.name }}</span>
                            </div>
                            <div v-if="sale.customer" class="flex justify-between">
                                <span>Customer</span>
                                <span>{{ sale.customer.name }} ({{ sale.customer.code }})</span>
                            </div>
                            <div v-if="sale.user" class="flex justify-between">
                                <span>Cashier</span>
                                <span>{{ sale.user.name }}</span>
                            </div>
                        </div>

                        <table class="w-full text-xs mb-3">
                            <thead>
                                <tr class="text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                    <th class="text-left py-1 font-medium">Item</th>
                                    <th class="text-right py-1 font-medium w-10">Qty</th>
                                    <th class="text-right py-1 font-medium w-16">Price</th>
                                    <th class="text-right py-1 font-medium w-20">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in sale.items || []" :key="item.id" class="align-top">
                                    <td class="py-1.5 pr-1">
                                        <div class="font-medium text-slate-900">{{ item.product?.name || '\u2014' }}</div>
                                        <div v-if="item.unit" class="text-slate-500">{{ item.unit.short_code || item.unit.name }}</div>
                                    </td>
                                    <td class="py-1.5 text-right">{{ item.quantity }}</td>
                                    <td class="py-1.5 text-right">{{ formatMoney(item.unit_price) }}</td>
                                    <td class="py-1.5 text-right font-medium">{{ formatMoney(item.line_total) }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <dl class="text-xs space-y-1 border-t border-slate-200 pt-2 mb-3">
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
                            <div class="flex justify-between pt-1 border-t border-slate-200">
                                <dt class="font-bold text-slate-900">TOTAL</dt>
                                <dd class="font-bold text-base text-slate-900">{{ formatMoney(sale.total) }}</dd>
                            </div>
                        </dl>

                        <div v-if="(sale.payments || []).length > 0" class="text-xs space-y-0.5 border-t border-slate-200 pt-2 mb-3">
                            <div v-for="(p, i) in sale.payments" :key="i" class="flex justify-between">
                                <span class="uppercase tracking-wide text-slate-500">{{ p.method.replace('_', ' ') }}</span>
                                <span class="text-slate-900">{{ formatMoney(p.amount) }}</span>
                            </div>
                            <div v-if="Number(sale.change_due) > 0" class="flex justify-between pt-1 border-t border-slate-200">
                                <span class="font-medium text-slate-900">Change</span>
                                <span class="font-bold text-emerald-700">{{ formatMoney(sale.change_due) }}</span>
                            </div>
                        </div>

                        <p class="text-center text-xs text-slate-500 mt-6">Thank you for your purchase.</p>
                    </div>

                    <div class="px-6 pb-6 flex items-center gap-2 print:hidden">
                        <BaseButton variant="secondary" class="flex-1" @click="$emit('close')">Close</BaseButton>
                        <BaseButton variant="primary" class="flex-1" @click="onPrint">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            Print receipt
                        </BaseButton>
                        <BaseButton variant="primary" class="flex-1" @click="$emit('new')">New sale</BaseButton>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import BaseButton from '@/components/ui/BaseButton.vue';
import { useCurrency } from '@/composables/useCurrency';

const props = defineProps({
    sale: { type: Object, required: true },
});
defineEmits(['close', 'new']);



function formatDateTime(value) {
    if (!value) return '\u2014';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleString();
}

function onPrint() {
    window.print();
}
</script>

<style>
@media print {
    @page { margin: 0.5cm; }
    body * { visibility: hidden; }
    .receipt-print, .receipt-print * { visibility: visible; }
    .receipt-print {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        padding: 1rem;
        background: white !important;
    }
    /* hide app chrome */
    aside, header, .app-sidebar, .app-navbar { display: none !important; }
}
</style>
