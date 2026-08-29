<template>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-50 via-white to-brand-50 px-4 py-12">
        <div class="w-full max-w-md">
            <div class="flex flex-col items-center mb-8">
                <div class="w-12 h-12 rounded-lg bg-brand-600 flex items-center justify-center text-white text-xl font-bold shadow-lg shadow-brand-600/30 mb-3">
                    W
                </div>
                <div class="text-[10px] uppercase tracking-widest text-brand-700 font-semibold mb-1">Wholesale Operations</div>
                <h1 class="text-2xl font-bold text-slate-900">Welcome back</h1>
                <p class="text-sm text-slate-500 mt-1">Sign in to continue · <span class="font-km">សូមចូលគណនីរបស់អ្នក</span></p>
            </div>

            <div class="bg-white rounded-lg shadow-xl shadow-slate-900/5 ring-1 ring-slate-200 p-8">
                <form @submit.prevent="handleLogin" novalidate class="space-y-5">
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
                        autocomplete="current-password"
                        placeholder="••••••••"
                        :error="errors.password"
                        @input="clearFieldError('password')"
                    />

                    <div
                        v-if="topError"
                        class="rounded-md bg-status-critical-bg border border-status-critical/30 px-4 py-3 text-sm text-status-critical flex items-start gap-2 font-mono"
                        role="alert"
                    >
                        <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>
                        </svg>
                        <span>{{ topError }}</span>
                    </div>

                    <BaseButton type="submit" :loading="auth.loading" block>
                        {{ auth.loading ? 'Signing in...' : 'Sign in' }}
                    </BaseButton>
                </form>
            </div>

            <p class="text-center text-sm text-slate-600 mt-6">
                Don't have an account?
                <router-link :to="{ name: 'register' }" class="font-semibold text-brand-600 hover:text-brand-700">Create one</router-link>
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
const form = reactive({ email: '', password: '' });
const errors = reactive({ email: '', password: '' });
const topError = ref('');

function clearFieldError(field) {
    errors[field] = '';
    if (topError.value) topError.value = '';
}

async function handleLogin() {
    errors.email = '';
    errors.password = '';
    topError.value = '';

    if (!form.email) {
        errors.email = 'Email is required.';
        return;
    }
    if (!form.password) {
        errors.password = 'Password is required.';
        return;
    }

    try {
        await auth.login(form);
        router.push({ name: 'dashboard' });
    } catch (e) {
        if (e.response?.status === 422 && e.response?.data?.errors) {
            const errs = e.response.data.errors;
            if (errs.email) errors.email = errs.email[0];
            if (errs.password) errors.password = errs.password[0];
            if (!errs.email && !errs.password) {
                topError.value = errs[Object.keys(errs)[0]][0];
            }
        } else if (e.response?.status === 401) {
            topError.value = 'Invalid email or password.';
        } else {
            topError.value = e.response?.data?.message || 'Login failed. Please try again.';
        }
    }
}
</script>
