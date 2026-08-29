<template>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-50 via-white to-brand-50 px-4 py-12">
        <div class="w-full max-w-md">
            <div class="flex flex-col items-center mb-8">
                <div class="w-12 h-12 rounded-xl bg-brand-600 flex items-center justify-center text-white text-xl font-bold shadow-lg shadow-brand-600/20 mb-3">
                    W
                </div>
                <h1 class="text-2xl font-bold text-slate-900">Create your account</h1>
                <p class="text-sm text-slate-500 mt-1">Start managing your wholesale operations</p>
            </div>

            <div class="bg-white rounded-2xl shadow-xl shadow-slate-900/5 ring-1 ring-slate-200 p-8">
                <form @submit.prevent="handleRegister" novalidate class="space-y-5">
                    <BaseInput
                        id="name"
                        v-model="form.name"
                        label="Full name"
                        required
                        autocomplete="name"
                        placeholder="Jane Doe"
                        :error="errors.name"
                        @input="clearFieldError('name')"
                    />
                    <BaseInput
                        id="email"
                        v-model="form.email"
                        label="Email"
                        type="email"
                        required
                        autocomplete="email"
                        placeholder="you@company.com"
                        :error="errors.email"
                        @input="clearFieldError('email')"
                    />
                    <BaseInput
                        id="password"
                        v-model="form.password"
                        label="Password"
                        type="password"
                        required
                        autocomplete="new-password"
                        hint="Must be at least 8 characters."
                        :error="errors.password"
                        @input="clearFieldError('password')"
                    />
                    <BaseInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        label="Confirm password"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••"
                        :error="errors.password_confirmation"
                        @input="clearFieldError('password_confirmation')"
                    />

                    <div
                        v-if="topError"
                        class="rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700 flex items-start gap-2"
                        role="alert"
                    >
                        <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>
                        </svg>
                        <span>{{ topError }}</span>
                    </div>

                    <BaseButton type="submit" :loading="auth.loading" block>
                        {{ auth.loading ? 'Creating account...' : 'Create account' }}
                    </BaseButton>
                </form>
            </div>

            <p class="text-center text-sm text-slate-600 mt-6">
                Already have an account?
                <router-link :to="{ name: 'login' }" class="font-semibold text-brand-600 hover:text-brand-700">Sign in</router-link>
            </p>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';

const auth = useAuthStore();
const router = useRouter();
const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});
const errors = reactive({ name: '', email: '', password: '', password_confirmation: '' });
const topError = ref('');

function clearFieldError(field) {
    errors[field] = '';
    if (topError.value) topError.value = '';
}

async function handleRegister() {
    Object.assign(errors, { name: '', email: '', password: '', password_confirmation: '' });
    topError.value = '';

    if (!form.name.trim()) {
        errors.name = 'Name is required.';
        return;
    }
    if (!form.email) {
        errors.email = 'Email is required.';
        return;
    }
    if (!form.password || form.password.length < 8) {
        errors.password = 'Password must be at least 8 characters.';
        return;
    }
    if (form.password !== form.password_confirmation) {
        errors.password_confirmation = 'Passwords do not match.';
        return;
    }

    try {
        await auth.register(form);
        router.push({ name: 'dashboard' });
    } catch (e) {
        if (e.response?.status === 422 && e.response?.data?.errors) {
            const errs = e.response.data.errors;
            Object.keys(errors).forEach(field => {
                if (errs[field]) errors[field] = errs[field][0];
            });
            const handled = Object.keys(errors).filter(f => errs[f]);
            if (handled.length === 0) {
                topError.value = e.response.data.message || 'Registration failed.';
            }
        } else {
            topError.value = e.response?.data?.message || 'Registration failed. Please try again.';
        }
    }
}
</script>
