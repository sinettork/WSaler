<script setup>
import { computed, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: 'Are you sure?' },
    message: { type: String, default: '' },
    impact: { type: String, default: '' },
    confirmLabel: { type: String, default: 'Confirm' },
    cancelLabel: { type: String, default: 'Cancel' },
    variant: { type: String, default: 'danger' }, // 'danger' | 'primary'
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['confirm', 'cancel', 'close']);

const confirmClasses = computed(() => {
    if (props.variant === 'danger') {
        return 'bg-rose-600 text-white hover:bg-rose-700 focus-visible:ring-rose-500';
    }
    return 'bg-brand-600 text-white hover:bg-brand-700 focus-visible:ring-brand-500';
});

const iconBgClasses = computed(() => {
    if (props.variant === 'danger') return 'bg-rose-100 text-rose-600';
    return 'bg-brand-100 text-brand-600';
});

function onConfirm() {
    emit('confirm');
}

function onCancel() {
    if (props.loading) return;
    emit('cancel');
    emit('close');
}

watch(() => props.show, (val) => {
    if (typeof document === 'undefined') return;
    document.body.style.overflow = val ? 'hidden' : '';
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                role="alertdialog"
                aria-modal="true"
                :aria-labelledby="`confirm-title`"
                :aria-describedby="message || impact ? `confirm-body` : undefined"
                @keydown.esc.stop="onCancel"
            >
                <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="onCancel"></div>
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-start gap-4">
                            <div :class="['shrink-0 w-10 h-10 rounded-full flex items-center justify-center', iconBgClasses]">
                                <svg v-if="variant === 'danger'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>
                                </svg>
                                <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 id="confirm-title" class="text-base font-semibold text-slate-900">{{ title }}</h3>
                                <p
                                    v-if="message || impact"
                                    id="confirm-body"
                                    class="mt-2 text-sm text-slate-600"
                                >
                                    <span v-if="message">{{ message }}</span>
                                    <span
                                        v-if="impact"
                                        class="block mt-2 text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-md px-3 py-2"
                                    >
                                        <strong class="font-medium text-slate-700">Impact:</strong> {{ impact }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2">
                        <button
                            type="button"
                            :disabled="loading"
                            class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
                            @click="onCancel"
                        >
                            {{ cancelLabel }}
                        </button>
                        <button
                            type="button"
                            :disabled="loading"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            :class="confirmClasses"
                            @click="onConfirm"
                        >
                            <svg v-if="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                            </svg>
                            {{ confirmLabel }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
