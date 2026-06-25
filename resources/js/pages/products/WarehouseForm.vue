<template>
    <div class="space-y-6">
        <div v-if="store.loading && editing" class="flex justify-center py-16">
            <Spinner size="lg" label="Loading warehouse..." />
        </div>

        <form v-else @submit.prevent="save" novalidate class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div class="min-w-0">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-2"
                        @click="$router.push('/warehouses')"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Back to warehouses
                    </button>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                        {{ editing ? `Update warehouse: ${form.name || '\u2014'}` : 'Create warehouse' }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ editing
                            ? 'Update contact info or settings for this warehouse.'
                            : 'Add a new storage location. A unique code will be assigned automatically.' }}
                    </p>
                </div>
            </div>

            <BaseCard>
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900">Identity</h2>
                </template>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <BaseInput
                        v-model="form.name"
                        name="name"
                        label="Name"
                        placeholder="e.g. Main Warehouse"
                        required
                        :error="errors.name"
                        @input="clearError('name')"
                    />
                    <BaseInput
                        v-if="editing"
                        v-model="form.code"
                        name="code"
                        label="Code"
                        hint="Auto-generated on creation."
                        disabled
                    />
                </div>
            </BaseCard>

            <BaseCard>
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900">Contact</h2>
                </template>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label for="warehouse-address" class="block text-sm font-medium text-slate-700 mb-1">Address</label>
                        <textarea
                            id="warehouse-address"
                            v-model="form.address"
                            name="address"
                            rows="2"
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:outline-none focus:ring-2 focus:border-brand-500 focus:ring-brand-500"
                            placeholder="Street, city, country"
                        />
                    </div>
                    <BaseInput
                        v-model="form.phone"
                        name="phone"
                        label="Phone"
                        placeholder="+1 555 0100"
                        :error="errors.phone"
                        @input="clearError('phone')"
                    />
                </div>
            </BaseCard>

            <BaseCard>
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900">Settings</h2>
                </template>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            v-model="form.is_default"
                            type="checkbox"
                            class="w-4 h-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                        />
                        <span class="text-sm text-slate-700">Set as default warehouse</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="w-4 h-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                        />
                        <span class="text-sm text-slate-700">Active &mdash; available for receiving stock</span>
                    </label>
                </div>
            </BaseCard>

            <div class="flex items-center justify-end gap-2 pt-2">
                <BaseButton type="button" variant="secondary" @click="$router.push('/warehouses')">
                    Cancel
                </BaseButton>
                <BaseButton type="submit" :loading="store.loading">
                    {{ editing ? 'Save changes' : 'Create warehouse' }}
                </BaseButton>
            </div>
        </form>
    </div>
</template>

<script setup>
import { reactive, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useWarehousesStore } from '@/stores/warehouses';
import { useToastStore } from '@/stores/toast';
import { useFormErrors } from '@/composables/useFormErrors';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import Spinner from '@/components/ui/Spinner.vue';

const route = useRoute();
const router = useRouter();
const store = useWarehousesStore();
const toast = useToastStore();
const errors = reactive({});
const { focusFirstError, clearErrors, clearError } = useFormErrors(errors);

const editing = computed(() => !!route.params.id);

const form = reactive({
    id: null,
    name: '',
    code: '',
    address: '',
    phone: '',
    is_default: false,
    is_active: true,
});

async function save() {
    clearErrors();
    try {
        const payload = { ...form };
        if (editing.value) {
            await store.update(form.id, payload);
            toast.success('Warehouse updated.');
        } else {
            delete payload.id;
            delete payload.code;
            await store.create(payload);
            toast.success('Warehouse created.');
        }
        router.push('/warehouses');
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
                code: data.code || '',
                address: data.address || '',
                phone: data.phone || '',
                is_default: !!data.is_default,
                is_active: data.is_active ?? true,
            });
        }
    }
});
</script>
