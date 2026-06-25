<template>
    <div class="space-y-6">
        <div v-if="store.loading && editing" class="flex justify-center py-16">
            <Spinner size="lg" label="Loading user..." />
        </div>

        <form v-else @submit.prevent="save" novalidate class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div class="min-w-0">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-2"
                        @click="$router.push('/admin/users')"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Back to users
                    </button>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                        {{ editing ? `Update user account: ${form.name || '\u2014'}` : 'Create user account' }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ editing
                            ? 'Change the name, email, or role for this user. Password is not changed here.'
                            : 'Create a new login for a staff member. They will use the password to sign in.' }}
                    </p>
                </div>
            </div>

            <BaseCard>
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900">Identity</h2>
                </template>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <BaseInput v-model="form.name" name="name" label="Full name" required :error="errors.name" @input="clearError('name')" />
                    <BaseInput v-model="form.email" name="email" type="email" label="Email" required :error="errors.email" @input="clearError('email')" hint="Used to sign in." />
                </div>
            </BaseCard>

            <BaseCard v-if="!editing">
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900">Access</h2>
                </template>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <BaseInput
                        v-model="form.password"
                        name="password"
                        type="password"
                        label="Password"
                        required
                        :error="errors.password"
                        hint="Minimum 8 characters."
                        @input="clearError('password')"
                    />
                    <BaseInput
                        v-model="form.password_confirmation"
                        name="password_confirmation"
                        type="password"
                        label="Confirm password"
                        required
                        :error="errors.password_confirmation"
                        @input="clearError('password_confirmation')"
                    />
                </div>
            </BaseCard>

            <BaseCard>
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900">Permissions</h2>
                </template>
                <BaseSelect
                    v-model="form.role"
                    name="role"
                    label="Role"
                    :options="[
                        { value: 'admin', label: 'Administrator' },
                        { value: 'manager', label: 'Manager' },
                        { value: 'cashier', label: 'Cashier' },
                        { value: 'warehouse', label: 'Warehouse Staff' },
                        { value: 'purchasing', label: 'Purchasing Staff' },
                        { value: 'delivery', label: 'Delivery Staff' },
                    ]"
                    required
                    :error="errors.role"
                    hint="Determines what this user can see and do."
                    @change="clearError('role')"
                />
            </BaseCard>

            <div class="flex items-center justify-end gap-2 pt-2">
                <BaseButton type="button" variant="secondary" @click="$router.push('/admin/users')">
                    Cancel
                </BaseButton>
                <BaseButton type="submit" :loading="store.loading">
                    {{ editing ? 'Save changes' : 'Create user' }}
                </BaseButton>
            </div>
        </form>
    </div>
</template>

<script setup>
import { reactive, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useUsersStore } from '@/stores/users';
import { useToastStore } from '@/stores/toast';
import { useFormErrors } from '@/composables/useFormErrors';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import Spinner from '@/components/ui/Spinner.vue';

const route = useRoute();
const router = useRouter();
const store = useUsersStore();
const toast = useToastStore();
const errors = reactive({});
const { focusFirstError, clearErrors, clearError } = useFormErrors(errors);

const editing = computed(() => !!route.params.id);

const form = reactive({
    id: null,
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'cashier',
});

async function save() {
    clearErrors();
    try {
        const payload = { ...form };
        if (editing.value) {
            delete payload.password;
            delete payload.password_confirmation;
            await store.update(form.id, payload);
            toast.success('User account updated.');
        } else {
            delete payload.id;
            await store.create(payload);
            toast.success('User account created.');
        }
        router.push('/admin/users');
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
                email: data.email || '',
                role: data.role || 'cashier',
            });
        }
    }
});
</script>
