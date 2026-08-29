<template>
    <div class="space-y-6">
        <div v-if="store.loading && editing" class="flex justify-center py-16">
            <Spinner size="lg" label="Loading category..." />
        </div>

        <form v-else @submit.prevent="save" novalidate class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div class="min-w-0">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-2"
                        @click="$router.push('/master/categories')"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Back to categories
                    </button>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                        {{ editing ? `Update category: ${form.name || '\u2014'}` : 'Create product category' }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ editing
                            ? 'Change the name, slug, parent, or active status for this category.'
                            : 'Add a new category to organize your products. Slug is auto-generated from the name if left blank.' }}
                    </p>
                </div>
            </div>

            <BaseCard>
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900">Category details</h2>
                </template>
                <div class="space-y-4">
                    <BaseInput
                        v-model="form.name"
                        name="name"
                        label="Name"
                        placeholder="e.g. Beverages"
                        required
                        :error="errors.name"
                        @input="(v) => { autoSlug(); clearError('name'); }"
                    />
                    <BaseInput
                        v-model="form.slug"
                        name="slug"
                        label="Slug"
                        placeholder="e.g. beverages"
                        required
                        :error="errors.slug"
                        hint="URL-friendly identifier (auto-generated if blank)"
                        @input="clearError('slug')"
                    />
                    <div>
                        <label for="category-description" class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                        <textarea
                            id="category-description"
                            v-model="form.description"
                            name="description"
                            rows="3"
                            class="block w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:outline-none focus:ring-2"
                            :class="errors.description ? 'border-rose-500 focus:ring-rose-500' : 'border-slate-300 focus:border-brand-500 focus:ring-brand-500'"
                            placeholder="Optional description visible to your team"
                        />
                        <p v-if="errors.description" class="mt-1 text-xs text-rose-600">{{ errors.description }}</p>
                    </div>
                    <BaseSelect
                        v-model="form.parent_id"
                        name="parent_id"
                        label="Parent category"
                        placeholder="\u2014 None \u2014"
                        :options="[{ value: null, label: '\u2014 None \u2014' }, ...availableParents.map(c => ({ value: c.id, label: c.name }))]"
                        :error="errors.parent_id"
                        hint="Make this a sub-category of another category."
                        @change="clearError('parent_id')"
                    />
                    <label class="flex items-center gap-2 cursor-pointer pt-2 border-t border-slate-100">
                        <input v-model="form.is_active" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
                        <span class="text-sm text-slate-700">Active &mdash; visible in product creation</span>
                    </label>
                </div>
            </BaseCard>

            <div class="flex items-center justify-end gap-2 pt-2">
                <BaseButton type="button" variant="secondary" @click="$router.push('/master/categories')">
                    Cancel
                </BaseButton>
                <BaseButton type="submit" :loading="store.loading">
                    {{ editing ? 'Save changes' : 'Create category' }}
                </BaseButton>
            </div>
        </form>
    </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useCategoriesStore } from '@/stores/categories';
import { useToastStore } from '@/stores/toast';
import { useFormErrors } from '@/composables/useFormErrors';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import Spinner from '@/components/ui/Spinner.vue';

const route = useRoute();
const router = useRouter();
const store = useCategoriesStore();
const toast = useToastStore();
const errors = reactive({});
const { focusFirstError, clearErrors, clearError } = useFormErrors(errors);

const editing = computed(() => !!route.params.id);
const list = ref([]);

const form = reactive({
    id: null,
    name: '',
    slug: '',
    description: '',
    parent_id: null,
    is_active: true,
});

const availableParents = computed(() => {
    if (!editing.value) return list.value;
    return list.value.filter((c) => c.id !== form.id);
});

function autoSlug() {
    if (!editing.value && !form.slug) {
        form.slug = form.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    }
}

async function save() {
    clearErrors();
    try {
        const payload = { ...form };
        if (editing.value) {
            await store.update(form.id, payload);
            toast.success('Category updated.');
        } else {
            delete payload.id;
            await store.create(payload);
            toast.success('Category created.');
        }
        router.push('/master/categories');
    } catch (e) {
        if (e.fieldErrors) {
            Object.assign(errors, e.fieldErrors);
            focusFirstError();
        }
    }
}

onMounted(async () => {
    // Fetch the list once so the parent dropdown is populated
    const res = await store.fetch({ per_page: 200 });
    list.value = store.items.length ? store.items : (res?.items || []);

    if (editing.value) {
        const row = await store.show(route.params.id);
        const data = row?.data || row;
        if (data) {
            Object.assign(form, {
                id: data.id,
                name: data.name || '',
                slug: data.slug || '',
                description: data.description || '',
                parent_id: data.parent_id ?? null,
                is_active: data.is_active ?? true,
            });
        }
    }
});
</script>
