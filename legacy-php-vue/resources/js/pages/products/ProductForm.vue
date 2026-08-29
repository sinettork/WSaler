<template>
    <div class="space-y-6">
        <div v-if="store.loading && editing" class="flex justify-center py-16">
            <Spinner size="lg" label="Loading product..." />
        </div>

        <form v-else @submit.prevent="save" novalidate class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
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
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                        {{ editing ? 'Edit Product' : 'Add Product' }}
                    </h1>
                    <p v-if="editing" class="mt-1 text-sm text-slate-500">Update product details, pricing and variations.</p>
                    <p v-else class="mt-1 text-sm text-slate-500">Create a new product in your catalog.</p>
                </div>
            </div>

            <BaseCard>
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900">Basic info</h2>
                </template>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <BaseInput
                        v-model="form.name"
                        name="name"
                        label="Name"
                        placeholder="e.g. Acme Widget Pro"
                        required
                        :error="errors.name"
                        @input="clearError('name')"
                    />
                    <BaseInput
                        v-model="form.sku"
                        name="sku"
                        label="SKU"
                        placeholder="Auto-generated if blank"
                        hint="Leave blank to auto-generate."
                        :error="errors.sku"
                        @input="clearError('sku')"
                    />
                    <div class="md:col-span-2">
                        <BaseInput
                            v-model="form.barcode"
                            name="barcode"
                            label="Barcode"
                            placeholder="UPC, EAN, etc."
                            :error="errors.barcode"
                            @input="clearError('barcode')"
                        />
                    </div>
                    <div class="md:col-span-2">
                        <label for="product-description" class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                        <textarea
                            id="product-description"
                            v-model="form.description"
                            name="description"
                            rows="3"
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:outline-none focus:ring-2 focus:border-brand-500 focus:ring-brand-500"
                            placeholder="Product description (optional)"
                        />
                    </div>
                </div>
            </BaseCard>

            <!-- Categorization -->
            <BaseCard>
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900">Categorization</h2>
                </template>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Category with inline create -->
                    <div>
                        <InlineQuickCreate v-model:open="categoryQuick.open" :loading="categoryQuick.creating" @create="createQuickCategory">
                            <template #field>
                                <BaseSelect
                                    v-model="form.category_id"
                                    name="category_id"
                                    label="Category"
                                    placeholder="Select category"
                                    :options="categoryStore.active.map(c => ({ value: c.id, label: c.name }))"
                                    required
                                    :error="errors.category_id"
                                    @change="clearError('category_id')"
                                />
                            </template>
                            <BaseInput
                                v-model="categoryQuick.form.name"
                                name="quick_category_name"
                                label="New category name"
                                placeholder="e.g. Beverages"
                                required
                                :error="categoryQuick.error"
                                @input="categoryQuick.error = ''"
                            />
                        </InlineQuickCreate>
                    </div>

                    <!-- Brand with inline create -->
                    <div>
                        <InlineQuickCreate v-model:open="brandQuick.open" :loading="brandQuick.creating" @create="createQuickBrand">
                            <template #field>
                                <BaseSelect
                                    v-model="form.brand_id"
                                    name="brand_id"
                                    label="Brand"
                                    placeholder="None"
                                    :options="[{ value: '', label: 'None' }, ...brandStore.active.map(b => ({ value: b.id, label: b.name }))]"
                                    :error="errors.brand_id"
                                    @change="clearError('brand_id')"
                                />
                            </template>
                            <BaseInput
                                v-model="brandQuick.form.name"
                                name="quick_brand_name"
                                label="New brand name"
                                placeholder="e.g. Acme"
                                required
                                :error="brandQuick.error"
                                @input="brandQuick.error = ''"
                            />
                        </InlineQuickCreate>
                    </div>

                    <!-- Base unit with inline create -->
                    <div>
                        <InlineQuickCreate v-model:open="unitQuick.open" :loading="unitQuick.creating" @create="createQuickUnit">
                            <template #field>
                                <div>
                                    <BaseSelect
                                        v-model="form.base_unit_id"
                                        name="base_unit_id"
                                        label="Base unit"
                                        placeholder="Select base unit"
                                        :options="unitStore.baseUnits.map(u => ({ value: u.id, label: `${u.name} (${u.short_code})` }))"
                                        required
                                        :error="errors.base_unit_id"
                                        @change="clearError('base_unit_id')"
                                    />
                                    <p v-if="baseUnitChanged" class="mt-1 text-xs text-amber-600">
                                        Warning: changing the base unit affects existing stock calculations.
                                    </p>
                                </div>
                            </template>
                            <div class="grid grid-cols-2 gap-2">
                                <BaseInput
                                    v-model="unitQuick.form.name"
                                    name="quick_unit_name"
                                    label="Name"
                                    placeholder="Kilogram"
                                    required
                                    :error="unitQuick.error"
                                    @input="unitQuick.error = ''"
                                />
                                <BaseInput
                                    v-model="unitQuick.form.short_code"
                                    name="quick_unit_short_code"
                                    label="Short code"
                                    placeholder="kg"
                                    required
                                />
                            </div>
                            <label class="flex items-center gap-2 cursor-pointer pt-1">
                                <input
                                    v-model="unitQuick.form.base"
                                    type="checkbox"
                                    class="w-4 h-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                />
                                <span class="text-sm text-slate-700">Base unit</span>
                            </label>
                        </InlineQuickCreate>
                    </div>
                </div>
            </BaseCard>

            <!-- Pricing -->
            <BaseCard>
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900">Pricing</h2>
                </template>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <BaseInput
                        v-model.number="form.retail_price"
                        name="retail_price"
                        type="number"
                        label="Retail price"
                        placeholder="0.00"
                        :error="errors.retail_price"
                        @input="clearError('retail_price')"
                    />
                    <BaseInput
                        v-model.number="form.wholesale_price"
                        name="wholesale_price"
                        type="number"
                        label="Wholesale price"
                        placeholder="0.00"
                        :error="errors.wholesale_price"
                        @input="clearError('wholesale_price')"
                    />
                    <BaseInput
                        v-model.number="form.distributor_price"
                        name="distributor_price"
                        type="number"
                        label="Distributor price"
                        placeholder="0.00"
                        :error="errors.distributor_price"
                        @input="clearError('distributor_price')"
                    />
                    <BaseInput
                        v-model.number="form.cost_price"
                        name="cost_price"
                        type="number"
                        label="Cost price"
                        placeholder="0.00"
                        :error="errors.cost_price"
                        @input="clearError('cost_price')"
                    />
                </div>
            </BaseCard>

            <BaseCard>
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900">Inventory</h2>
                </template>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <BaseSelect
                        v-model="form.status"
                        name="status"
                        label="Status"
                        :options="[
                            { value: 'active', label: 'Active' },
                            { value: 'inactive', label: 'Inactive' },
                        ]"
                    />
                    <label class="flex items-end pb-2 cursor-pointer">
                        <input
                            v-model="form.track_stock"
                            type="checkbox"
                            class="w-4 h-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                        />
                        <span class="ml-2 text-sm text-slate-700">Track stock for this product</span>
                    </label>
                </div>
            </BaseCard>

            <BaseCard>
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900">Image</h2>
                </template>
                <div class="flex items-start gap-5">
                    <div class="shrink-0 w-32 h-32 rounded-lg border border-slate-200 bg-slate-50 flex items-center justify-center overflow-hidden">
                        <img v-if="imagePreview" :src="imagePreview" alt="preview" class="w-full h-full object-cover" />
                        <svg v-else class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <input
                            type="file"
                            accept="image/*"
                            class="block w-full text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100"
                            @change="onImageChange"
                        />
                        <p class="mt-2 text-xs text-slate-500">Max 2 MB. JPG, PNG.</p>
                        <button
                            v-if="imagePreview"
                            type="button"
                            class="mt-2 text-xs text-rose-600 hover:text-rose-700"
                            @click="clearImage"
                        >
                            Remove image
                        </button>
                    </div>
                </div>
            </BaseCard>

            <BaseCard v-if="auth.hasRole(['admin','manager'])">
                <template #header>
                    <div class="flex items-center justify-between w-full">
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900">Variations</h2>
                            <p class="mt-0.5 text-xs text-slate-500">{{ form.variations.length }} variation(s)</p>
                        </div>
                        <BaseButton size="sm" variant="secondary" type="button" @click="addVariation">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add variation
                        </BaseButton>
                    </div>
                </template>
                <EmptyState
                    v-if="form.variations.length === 0"
                    title="No variations yet"
                    description="Add variations like size, color, or pack size (e.g. 24-Pack box deducts 24 base units per scan)."
                    icon="tag"
                />
                <div v-else class="space-y-4">
                    <div
                        v-for="(v, index) in form.variations"
                        :key="index"
                        class="border border-slate-200 rounded-lg p-4"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Variation #{{ index + 1 }}
                            </span>
                            <button
                                type="button"
                                class="text-xs text-rose-600 hover:text-rose-700"
                                @click="removeVariation(index)"
                            >
                                Remove
                            </button>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                            <BaseInput v-model="v.name" name="variation_name" label="Name" placeholder="e.g. Size" required />
                            <BaseInput v-model="v.value" name="variation_value" label="Value" placeholder="e.g. Large" required />
                            <BaseInput v-model="v.sku_suffix" name="variation_sku_suffix" label="SKU suffix" placeholder="Optional" />
                            <BaseInput v-model="v.barcode" name="variation_barcode" label="Barcode" placeholder="Optional" />
                            <BaseInput
                                v-model.number="v.additional_price"
                                name="variation_additional_price"
                                type="number"
                                label="Additional price"
                                placeholder="0.00"
                            />
                            <BaseInput
                                v-model.number="v.quantity_multiplier"
                                name="variation_quantity_multiplier"
                                type="number"
                                label="Pack size"
                                placeholder="1"
                                hint="Base units per pack sold (e.g. 24)"
                                required
                            />
                        </div>
                    </div>
                </div>
            </BaseCard>

            <div class="flex items-center justify-end gap-2 pt-2">
                <BaseButton type="button" variant="secondary" @click="$router.push('/products')">
                    Cancel
                </BaseButton>
                <BaseButton type="submit" :loading="store.loading">
                    {{ editing ? 'Save changes' : 'Create product' }}
                </BaseButton>
            </div>
        </form>
    </div>
</template>

<script setup>
import { reactive, ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useProductsStore } from '@/stores/products';
import { useCategoriesStore } from '@/stores/categories';
import { useBrandsStore } from '@/stores/brands';
import { useUnitsStore } from '@/stores/units';
import { useToastStore } from '@/stores/toast';
import { useFormErrors } from '@/composables/useFormErrors';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import Spinner from '@/components/ui/Spinner.vue';
import InlineQuickCreate from '@/components/InlineQuickCreate.vue';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const store = useProductsStore();
const categoryStore = useCategoriesStore();
const brandStore = useBrandsStore();
const unitStore = useUnitsStore();
const toast = useToastStore();
const errors = reactive({});
const { focusFirstError, clearErrors, clearError } = useFormErrors(errors);

const editing = computed(() => !!route.params.id);
const originalBaseUnitId = ref('');

const form = reactive({
    id: null,
    name: '',
    sku: '',
    barcode: '',
    description: '',
    category_id: '',
    brand_id: '',
    base_unit_id: '',
    retail_price: 0,
    wholesale_price: 0,
    distributor_price: 0,
    cost_price: 0,
    status: 'active',
    track_stock: true,
    image: null,
    variations: [],
});

const imagePreview = ref('');
const imageFile = ref(null);

const baseUnitChanged = computed(
    () => editing.value && originalBaseUnitId.value && form.base_unit_id !== originalBaseUnitId.value
);

// Inline quick-create state
const categoryQuick = reactive({
    open: false,
    form: { name: '' },
    creating: false,
    error: '',
});
const brandQuick = reactive({
    open: false,
    form: { name: '' },
    creating: false,
    error: '',
});
const unitQuick = reactive({
    open: false,
    form: { name: '', short_code: '', base: true, conversion_factor_to_base: 1 },
    creating: false,
    error: '',
});

function extractNewItem(res) {
    return res?.data?.data || res?.data || res;
}

async function createQuickCategory() {
    if (!categoryQuick.form.name.trim()) {
        categoryQuick.error = 'Name is required.';
        return;
    }
    categoryQuick.creating = true;
    categoryQuick.error = '';
    try {
        const res = await categoryStore.create({
            name: categoryQuick.form.name,
            is_active: true,
        });
        const created = extractNewItem(res);
        if (created?.id) {
            form.category_id = created.id;
            clearError('category_id');
        }
        categoryQuick.form.name = '';
        categoryQuick.error = '';
        categoryQuick.open = false;
        toast.success('Category created.');
    } catch (e) {
        categoryQuick.error = e.fieldErrors?.name || 'Could not create category.';
    } finally {
        categoryQuick.creating = false;
    }
}

async function createQuickBrand() {
    if (!brandQuick.form.name.trim()) {
        brandQuick.error = 'Name is required.';
        return;
    }
    brandQuick.creating = true;
    brandQuick.error = '';
    try {
        const res = await brandStore.create({
            name: brandQuick.form.name,
            is_active: true,
        });
        const created = extractNewItem(res);
        if (created?.id) {
            form.brand_id = created.id;
            clearError('brand_id');
        }
        brandQuick.form.name = '';
        brandQuick.error = '';
        brandQuick.open = false;
        toast.success('Brand created.');
    } catch (e) {
        brandQuick.error = e.fieldErrors?.name || 'Could not create brand.';
    } finally {
        brandQuick.creating = false;
    }
}

async function createQuickUnit() {
    if (!unitQuick.form.name.trim()) {
        unitQuick.error = 'Name is required.';
        return;
    }
    if (!unitQuick.form.short_code.trim()) {
        unitQuick.error = 'Short code is required.';
        return;
    }
    unitQuick.creating = true;
    unitQuick.error = '';
    try {
        const payload = {
            name: unitQuick.form.name,
            short_code: unitQuick.form.short_code,
            base: unitQuick.form.base,
            conversion_factor_to_base: unitQuick.form.base ? 1 : (unitQuick.form.conversion_factor_to_base || 1),
        };
        const res = await unitStore.create(payload);
        const created = extractNewItem(res);
        if (created?.id) {
            form.base_unit_id = created.id;
            clearError('base_unit_id');
        }
        unitQuick.form.name = '';
        unitQuick.form.short_code = '';
        unitQuick.form.base = true;
        unitQuick.form.conversion_factor_to_base = 1;
        unitQuick.error = '';
        unitQuick.open = false;
        toast.success('Unit created.');
    } catch (e) {
        if (e.fieldErrors?.name) unitQuick.error = e.fieldErrors.name;
        else if (e.fieldErrors?.short_code) unitQuick.error = e.fieldErrors.short_code;
        else unitQuick.error = 'Could not create unit.';
    } finally {
        unitQuick.creating = false;
    }
}

function onImageChange(e) {
    const f = e.target.files[0];
    if (!f) return;
    if (f.size > 2 * 1024 * 1024) {
        toast.error('Image must be under 2 MB.');
        return;
    }
    imageFile.value = f;
    imagePreview.value = URL.createObjectURL(f);
}

function clearImage() {
    imageFile.value = null;
    imagePreview.value = '';
}

function addVariation() {
    form.variations.push({
        name: '',
        value: '',
        sku_suffix: '',
        barcode: '',
        additional_price: 0,
        quantity_multiplier: 1,
    });
}

function removeVariation(index) {
    form.variations.splice(index, 1);
}

async function save() {
    clearErrors();
    try {
        const payload = { ...form };
        delete payload.id;
        if (imageFile.value) payload.image = imageFile.value;
        const fd = store.buildFormData(payload);
        if (editing.value) {
            await store.update(route.params.id, fd);
            toast.success('Product updated.');
            router.push('/products/' + route.params.id);
        } else {
            const res = await store.create(fd);
            const newId = res?.data?.id || res?.id || res?.data?.data?.id;
            toast.success('Product created.');
            router.push('/products/' + newId);
        }
    } catch (e) {
        if (e.fieldErrors) {
            Object.assign(errors, e.fieldErrors);
            focusFirstError();
        }
    }
}

onMounted(async () => {
    await Promise.all([
        categoryStore.fetch(),
        brandStore.fetch(),
        unitStore.fetch(),
    ]);

    if (editing.value) {
        const product = await store.get(route.params.id);
        if (product) {
            const p = product.data || product;
            Object.assign(form, {
                name: p.name || '',
                sku: p.sku || '',
                barcode: p.barcode || '',
                description: p.description || '',
                category_id: p.category_id || p.category?.id || '',
                brand_id: p.brand_id || p.brand?.id || '',
                base_unit_id: p.base_unit_id || p.base_unit?.id || '',
                retail_price: p.retail_price || 0,
                wholesale_price: p.wholesale_price || 0,
                distributor_price: p.distributor_price || 0,
                cost_price: p.cost_price || 0,
                status: p.status || 'active',
                track_stock: p.track_stock ?? true,
                variations: (p.variations || []).map(v => ({
                    id: v.id,
                    name: v.name,
                    value: v.value,
                    sku_suffix: v.sku_suffix || '',
                    barcode: v.barcode || '',
                    additional_price: v.additional_price || 0,
                    quantity_multiplier: v.quantity_multiplier ?? 1,
                })),
            });
            originalBaseUnitId.value = form.base_unit_id;
            imagePreview.value = p.image_url || '';
        }
    }
});
</script>
