<template>
    <div class="space-y-6">
        <div v-if="store.loading && editing" class="flex justify-center py-16">
            <Spinner size="lg" label="Loading supplier..." />
        </div>

        <form v-else @submit.prevent="save" novalidate class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div class="min-w-0">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-2"
                        @click="$router.push('/master/suppliers')"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Back to suppliers
                    </button>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                        {{ editing ? `Update supplier: ${form.name || '\u2014'}` : 'Create supplier' }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ editing
                            ? 'Update contact, address, or terms for this supplier.'
                            : 'Add a new supplier. Contact and tax fields are optional but help with purchase orders.' }}
                    </p>
                </div>
            </div>

            <BaseCard>
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900">Identity</h2>
                </template>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <BaseInput v-model="form.name" name="name" label="Name" required :error="errors.name" @input="clearError('name')" />
                    <BaseInput v-model="form.contact_person" name="contact_person" label="Contact person" :error="errors.contact_person" @input="clearError('contact_person')" />
                </div>
            </BaseCard>

            <BaseCard>
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900">Contact</h2>
                </template>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <BaseInput v-model="form.email" name="email" type="email" label="Email" :error="errors.email" @input="clearError('email')" />
                    <BaseInput v-model="form.phone" name="phone" label="Phone" :error="errors.phone" @input="clearError('phone')" />
                    <div class="md:col-span-2">
                        <label for="supplier-address" class="block text-sm font-medium text-slate-700 mb-1">Address</label>
                        <textarea
                            id="supplier-address"
                            v-model="form.address"
                            name="address"
                            rows="2"
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:outline-none focus:ring-2 focus:border-brand-500 focus:ring-brand-500"
                            placeholder="Street, city, country"
                        />
                    </div>
                </div>
            </BaseCard>

            <BaseCard>
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900">Commercial terms</h2>
                </template>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <BaseInput v-model="form.tax_number" name="tax_number" label="Tax number" :error="errors.tax_number" @input="clearError('tax_number')" />
                    <BaseInput v-model="form.payment_terms" name="payment_terms" label="Payment terms" placeholder="e.g. Net 30" :error="errors.payment_terms" @input="clearError('payment_terms')" />
                    <div class="md:col-span-2">
                        <label for="supplier-notes" class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                        <textarea
                            id="supplier-notes"
                            v-model="form.notes"
                            name="notes"
                            rows="2"
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:outline-none focus:ring-2 focus:border-brand-500 focus:ring-brand-500"
                            placeholder="Internal notes about this supplier"
                        />
                    </div>
                </div>
                <label class="flex items-center gap-2 cursor-pointer pt-4 mt-4 border-t border-slate-100">
                    <input v-model="form.is_active" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
                    <span class="text-sm text-slate-700">Active &mdash; available for purchase orders</span>
                </label>
            </BaseCard>

            <div class="flex items-center justify-end gap-2 pt-2">
                <BaseButton type="button" variant="secondary" @click="$router.push('/master/suppliers')">
                    Cancel
                </BaseButton>
                <BaseButton type="submit" :loading="store.loading">
                    {{ editing ? 'Save changes' : 'Create supplier' }}
                </BaseButton>
            </div>
        </form>
    </div>
</template>

<script setup>
import { reactive, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useSuppliersStore } from '@/stores/suppliers';
import { useToastStore } from '@/stores/toast';
import { useFormErrors } from '@/composables/useFormErrors';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import Spinner from '@/components/ui/Spinner.vue';

const route = useRoute();
const router = useRouter();
const store = useSuppliersStore();
const toast = useToastStore();
const errors = reactive({});
const { focusFirstError, clearErrors, clearError } = useFormErrors(errors);

const editing = computed(() => !!route.params.id);

const form = reactive({
    id: null, name: '', contact_person: '', email: '', phone: '',
    address: '', tax_number: '', payment_terms: '', notes: '', is_active: true,
});

async function save() {
    clearErrors();
    try {
        const payload = { ...form };
        if (editing.value) {
            await store.update(form.id, payload);
            toast.success('Supplier updated.');
        } else {
            delete payload.id;
            await store.create(payload);
            toast.success('Supplier created.');
        }
        router.push('/master/suppliers');
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
                contact_person: data.contact_person || '',
                email: data.email || '',
                phone: data.phone || '',
                address: data.address || '',
                tax_number: data.tax_number || '',
                payment_terms: data.payment_terms || '',
                notes: data.notes || '',
                is_active: data.is_active ?? true,
            });
        }
    }
});
</script>
