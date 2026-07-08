<template>
    <div>
        <div v-if="loading" class="flex justify-center py-16">
            <Spinner size="lg" label="Loading product..." />
        </div>

        <div v-else-if="product">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
                <div class="min-w-0">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-2"
                        @click="$router.push('/products')"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Back to products
                    </button>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ product.name }}</h1>
                    <p class="mt-1 text-sm text-slate-500">SKU: {{ product.sku || '—' }}</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <BaseButton
                        v-if="auth.hasRole(['admin','manager'])"
                        @click="$router.push('/products/' + product.id + '/edit')"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit
                    </BaseButton>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left: image + basic info -->
                <div class="space-y-6">
                    <BaseCard padding="none">
                        <div class="aspect-square bg-slate-50 flex items-center justify-center overflow-hidden rounded-t-xl">
                            <img
                                v-if="product.image_url"
                                :src="product.image_url"
                                :alt="product.name"
                                class="w-full h-full object-contain"
                            />
                            <svg v-else class="w-24 h-24 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                    </BaseCard>

                    <BaseCard>
                        <template #header>
                            <h2 class="text-sm font-semibold text-slate-900">Basic info</h2>
                        </template>
                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">SKU</dt>
                                <dd class="font-medium text-slate-900">{{ product.sku || '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Barcode</dt>
                                <dd class="font-medium text-slate-900">{{ product.barcode || '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Status</dt>
                                <dd>
                                    <BaseBadge :variant="product.status === 'active' ? 'success' : 'neutral'">
                                        {{ product.status || '—' }}
                                    </BaseBadge>
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Category</dt>
                                <dd class="font-medium text-slate-900">{{ product.category?.name || '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Brand</dt>
                                <dd class="font-medium text-slate-900">{{ product.brand?.name || '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Base unit</dt>
                                <dd class="font-medium text-slate-900">{{ product.base_unit?.name || '—' }}</dd>
                            </div>
                        </dl>
                    </BaseCard>

                    <BaseCard>
                        <template #header>
                            <h2 class="text-sm font-semibold text-slate-900">Pricing</h2>
                        </template>
                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Retail</dt>
                                <dd class="font-medium text-slate-900">{{ formatMoney(product.retail_price) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Wholesale</dt>
                                <dd class="font-medium text-slate-900">{{ formatMoney(product.wholesale_price) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Distributor</dt>
                                <dd class="font-medium text-slate-900">{{ formatMoney(product.distributor_price) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 pt-2 border-t border-slate-100">
                                <dt class="text-slate-500">Cost</dt>
                                <dd class="font-medium text-slate-900">{{ formatMoney(product.cost_price) }}</dd>
                            </div>
                        </dl>
                    </BaseCard>

                    <BaseCard>
                        <template #header>
                            <h2 class="text-sm font-semibold text-slate-900">Stock</h2>
                        </template>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-slate-500">Total stock</p>
                                <p class="mt-1 text-xl font-bold" :class="(product.total_stock ?? 0) > 0 ? 'text-emerald-700' : 'text-rose-700'">
                                    {{ product.total_stock ?? 0 }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Near expiry</p>
                                <p class="mt-1 text-xl font-bold" :class="(product.near_expiry_stock ?? 0) > 0 ? 'text-amber-700' : 'text-slate-400'">
                                    {{ product.near_expiry_stock ?? 0 }}
                                </p>
                            </div>
                        </div>
                    </BaseCard>
                </div>

                <!-- Middle: variations -->
                <BaseCard padding="none">
                    <div class="px-5 py-4 border-b border-slate-200">
                        <h2 class="text-sm font-semibold text-slate-900">Variations</h2>
                        <p class="mt-0.5 text-xs text-slate-500">{{ (product.variations || []).length }} variation(s)</p>
                    </div>
                    <div v-if="!product.variations || product.variations.length === 0">
                        <EmptyState title="No variations" description="This product has no defined variations." icon="tag" />
                    </div>
                    <ul v-else class="divide-y divide-slate-100">
                        <li v-for="v in product.variations" :key="v.id" class="px-5 py-3 text-sm">
                            <div class="flex items-baseline justify-between gap-2">
                                <span class="font-medium text-slate-900">{{ v.name }}: {{ v.value }}</span>
                                <span v-if="v.additional_price" class="text-xs text-slate-500">+{{ formatMoney(v.additional_price) }}</span>
                            </div>
                            <div class="mt-1 text-xs text-slate-500 flex flex-wrap gap-x-3 gap-y-0.5">
                                <span>SKU suffix: <span class="text-slate-700">{{ v.sku_suffix || '—' }}</span></span>
                                <span>Barcode: <span class="text-slate-700">{{ v.barcode || '—' }}</span></span>
                            </div>
                        </li>
                    </ul>
                </BaseCard>

                <!-- Right: recent batches -->
                <BaseCard padding="none">
                    <div class="px-5 py-4 border-b border-slate-200">
                        <h2 class="text-sm font-semibold text-slate-900">Recent batches</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Last {{ (product.batches || []).length }} batch(es)</p>
                    </div>
                    <div v-if="!product.batches || product.batches.length === 0">
                        <EmptyState title="No batches" description="No batches have been received for this product yet." icon="box" />
                    </div>
                    <ul v-else class="divide-y divide-slate-100">
                        <li v-for="b in product.batches" :key="b.id" class="px-5 py-3 text-sm">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-medium text-slate-900">{{ b.batch_number }}</span>
                                <BaseBadge :variant="expiryVariant(b.expiry_status)">
                                    {{ b.expiry_date ? new Date(b.expiry_date).toLocaleDateString() : '—' }}
                                </BaseBadge>
                            </div>
                            <div class="mt-1 text-xs text-slate-500 flex flex-wrap gap-x-3 gap-y-0.5">
                                <span>Remaining: <span class="text-slate-700">{{ b.remaining_quantity }}</span></span>
                                <span>Warehouse: <span class="text-slate-700">{{ b.warehouse?.name || '—' }}</span></span>
                            </div>
                        </li>
                    </ul>
                </BaseCard>
            </div>
        </div>

        <div v-else class="bg-white border border-amber-200 rounded-xl p-6 text-sm text-amber-800">
            Product not found.
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useProductsStore } from '@/stores/products';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import BaseBadge from '@/components/ui/BaseBadge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import Spinner from '@/components/ui/Spinner.vue';
import { useCurrency } from '@/composables/useCurrency';

const route = useRoute();
const auth = useAuthStore();
const store = useProductsStore();
const { formatMoney } = useCurrency();

const product = computed(() => store.current);
const loading = computed(() => store.loading && !store.current);

function expiryVariant(status) {
    switch (status) {
        case 'expired':
        case 'critical':
            return 'danger';
        case 'warning':
            return 'warning';
        case 'notice':
            return 'info';
        case 'good':
            return 'success';
        default:
            return 'neutral';
    }
}

onMounted(() => store.get(route.params.id));
</script>
