<template>
    <div class="space-y-6">
        <div v-if="store.loading && editing" class="flex justify-center py-16">
            <Spinner size="lg" label="Loading customer..." />
        </div>

        <form v-else @submit.prevent="save" novalidate class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div class="min-w-0">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-2"
                        @click="$router.push('/master/customers')"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Back to customers
                    </button>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                        {{ editing ? `Update customer: ${form.name || '\u2014'}` : 'Create customer' }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ editing
                            ? 'Update contact info, type, or credit terms for this customer.'
                            : 'Add a new customer. A unique code will be assigned automatically.' }}
                    </p>
                </div>
            </div>

            <BaseCard>
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900">Identity</h2>
                </template>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-if="editing">
                        <BaseInput v-model="form.code" name="code" label="Code" disabled hint="Auto-assigned on creation." />
                    </div>
                    <BaseInput
                        v-model="form.name"
                        name="name"
                        label="Name"
                        required
                        :error="errors.name"
                        @input="clearError('name')"
                        :class="editing ? '' : 'md:col-span-2'"
                    />
                    <BaseInput v-model="form.contact_person" name="contact_person" label="Contact person" :error="errors.contact_person" @input="clearError('contact_person')" />
                    <BaseSelect
                        v-model="form.type"
                        name="type"
                        label="Customer type"
                        :options="[
                            { value: 'retail', label: 'Retail' },
                            { value: 'wholesale', label: 'Wholesale' },
                            { value: 'distributor', label: 'Distributor' },
                            { value: 'vip', label: 'VIP' },
                        ]"
                        :error="errors.type"
                        @change="clearError('type')"
                    />
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
                        <AddressCascader
                            v-model="form.addresses"
                            :required="{ province: true, district: true, commune: true }"
                            :errors="errors"
                        />
                    </div>
                </div>
            </BaseCard>

            <BaseCard>
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900">Credit &amp; terms</h2>
                </template>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <BaseInput
                        v-model.number="form.credit_limit"
                        name="credit_limit"
                        type="number"
                        label="Credit limit"
                        placeholder="0.00"
                        :error="errors.credit_limit"
                        @input="clearError('credit_limit')"
                    />
                    <div v-if="editing">
                        <BaseInput
                            v-model.number="form.current_balance"
                            name="current_balance"
                            type="number"
                            label="Current balance"
                            disabled
                        />
                    </div>
                    <BaseInput v-model="form.payment_terms" name="payment_terms" label="Payment terms" placeholder="e.g. Net 30" :error="errors.payment_terms" @input="clearError('payment_terms')" />
                    <div class="md:col-span-2">
                        <label for="customer-notes" class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                        <textarea
                            id="customer-notes"
                            v-model="form.notes"
                            name="notes"
                            rows="2"
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:outline-none focus:ring-2 focus:border-brand-500 focus:ring-brand-500"
                            placeholder="Internal notes"
                        />
                    </div>
                </div>
                <label class="flex items-center gap-2 cursor-pointer pt-4 mt-4 border-t border-slate-100">
                    <input v-model="form.is_active" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
                    <span class="text-sm text-slate-700">Active &mdash; available for sales</span>
                </label>
            </BaseCard>

            <div class="flex items-center justify-end gap-2 pt-2">
                <BaseButton type="button" variant="secondary" @click="$router.push('/master/customers')">
                    Cancel
                </BaseButton>
                <BaseButton type="submit" :loading="store.loading">
                    {{ editing ? 'Save changes' : 'Create customer' }}
                </BaseButton>
            </div>
        </form>
    </div>
</template>

<script setup>
import { reactive, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useCustomersStore } from '@/stores/customers';
import { useToastStore } from '@/stores/toast';
import { useFormErrors } from '@/composables/useFormErrors';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import Spinner from '@/components/ui/Spinner.vue';
import AddressCascader from '@/components/AddressCascader.vue';

const route = useRoute();
const router = useRouter();
const store = useCustomersStore();
const toast = useToastStore();
const errors = reactive({});
const { focusFirstError, clearErrors, clearError } = useFormErrors(errors);

const editing = computed(() => !!route.params.id);

const form = reactive({
    id: null, code: '', name: '', contact_person: '', email: '', phone: '',
    addresses: { province_id: null, district_id: null, commune_id: null, village_id: null, address: '' },
    type: 'retail', credit_limit: 0, current_balance: 0,
    payment_terms: '', notes: '', is_active: true,
});

async function save() {
    clearErrors();
    try {
        const payload = {
            ...form,
            province_id: form.addresses.province_id,
            district_id: form.addresses.district_id,
            commune_id: form.addresses.commune_id,
            village_id: form.addresses.village_id,
            address: form.addresses.address,
        };
        delete payload.addresses;
        if (editing.value) {
            await store.update(form.id, payload);
            toast.success('Customer updated.');
        } else {
            delete payload.id;
            delete payload.code;
            delete payload.current_balance;
            await store.create(payload);
            toast.success('Customer created.');
        }
        router.push('/master/customers');
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
                code: data.code || '',
                name: data.name || '',
                contact_person: data.contact_person || '',
                email: data.email || '',
                phone: data.phone || '',
                addresses: {
                    province_id: data.province_id ?? null,
                    district_id: data.district_id ?? null,
                    commune_id: data.commune_id ?? null,
                    village_id: data.village_id ?? null,
                    address: data.address || '',
                },
                type: data.type || 'retail',
                credit_limit: data.credit_limit ?? 0,
                current_balance: data.current_balance ?? 0,
                payment_terms: data.payment_terms || '',
                notes: data.notes || '',
                is_active: data.is_active ?? true,
            });
        }
    }
});
</script>
