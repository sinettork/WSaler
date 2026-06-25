<template>
    <div class="space-y-6">
        <div v-if="store.loading && editing" class="flex justify-center py-16">
            <Spinner size="lg" label="Loading brand..." />
        </div>

        <form v-else @submit.prevent="save" novalidate class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div class="min-w-0">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-2"
                        @click="$router.push('/master/brands')"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Back to brands
                    </button>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                        {{ editing ? `Update brand: ${form.name || '\u2014'}` : 'Create product brand' }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ editing
                            ? 'Change the name, description, or logo for this brand.'
                            : 'Add a new brand to label your products. The slug is auto-generated from the name.' }}
                    </p>
                </div>
            </div>

            <BaseCard>
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900">Brand details</h2>
                </template>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <BaseInput
                            v-model="form.name"
                            name="name"
                            label="Name"
                            placeholder="e.g. Acme"
                            required
                            :error="errors.name"
                            @input="(v) => { autoSlug(); clearError('name'); }"
                        />
                        <BaseInput
                            v-model="form.slug"
                            name="slug"
                            label="Slug"
                            placeholder="e.g. acme"
                            required
                            :error="errors.slug"
                            hint="URL-friendly identifier (auto-generated if blank)"
                            @input="clearError('slug')"
                        />
                    </div>
                    <div>
                        <label for="brand-description" class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                        <textarea
                            id="brand-description"
                            v-model="form.description"
                            name="description"
                            rows="3"
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:outline-none focus:ring-2 focus:border-brand-500 focus:ring-brand-500"
                            placeholder="Optional description"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Logo</label>
                        <input
                            type="file"
                            accept="image/*"
                            class="block w-full text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100"
                            @change="onFileChange"
                        />
                        <img v-if="logoPreview" :src="logoPreview" class="mt-3 max-h-24 rounded border border-slate-200" />
                        <p class="mt-1 text-xs text-slate-500">Recommended: square PNG or JPG, max 2 MB.</p>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer pt-2 border-t border-slate-100">
                        <input v-model="form.is_active" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
                        <span class="text-sm text-slate-700">Active &mdash; visible in product creation</span>
                    </label>
                </div>
            </BaseCard>

            <div class="flex items-center justify-end gap-2 pt-2">
                <BaseButton type="button" variant="secondary" @click="$router.push('/master/brands')">
                    Cancel
                </BaseButton>
                <BaseButton type="submit" :loading="store.loading">
                    {{ editing ? 'Save changes' : 'Create brand' }}
                </BaseButton>
            </div>
        </form>
    </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useBrandsStore } from '@/stores/brands';
import { useToastStore } from '@/stores/toast';
import { useFormErrors } from '@/composables/useFormErrors';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import Spinner from '@/components/ui/Spinner.vue';

const route = useRoute();
const router = useRouter();
const store = useBrandsStore();
const toast = useToastStore();
const errors = reactive({});
const { focusFirstError, clearErrors, clearError } = useFormErrors(errors);

const editing = computed(() => !!route.params.id);

const form = reactive({
    id: null,
    name: '',
    slug: '',
    description: '',
    is_active: true,
});
const logoFile = ref(null);
const logoPreview = ref(null);

function autoSlug() {
    if (!editing.value && !form.slug) {
        form.slug = form.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    }
}

function onFileChange(e) {
    const f = e.target.files[0];
    if (f) {
        logoFile.value = f;
        logoPreview.value = URL.createObjectURL(f);
    }
}

async function save() {
    clearErrors();
    try {
        const payload = new FormData();
        payload.append('name', form.name);
        payload.append('slug', form.slug);
        if (form.description) payload.append('description', form.description);
        payload.append('is_active', form.is_active ? '1' : '0');
        if (logoFile.value) payload.append('logo', logoFile.value);
        if (editing.value) {
            await store.update(form.id, payload);
            toast.success('Brand updated.');
        } else {
            await store.create(payload);
            toast.success('Brand created.');
        }
        router.push('/master/brands');
    } catch (e) {
        if (e.fieldErrors) {
            Object.assign(errors, e.fieldErrors);
            focusFirstError();
        }
    }
}

onMounted(async () => {
    if (editing.value) {
        const row = await store.show(route.params.id);
        const data = row?.data || row;
        if (data) {
            Object.assign(form, {
                id: data.id,
                name: data.name || '',
                slug: data.slug || '',
                description: data.description || '',
                is_active: data.is_active ?? true,
            });
            logoPreview.value = data.logo_url || null;
        }
    }
});
</script>
