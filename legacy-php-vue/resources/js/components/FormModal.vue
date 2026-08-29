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
                role="dialog"
                aria-modal="true"
                :aria-labelledby="titleId"
                :aria-describedby="descriptionId"
                @keydown.esc.stop="onCancel"
                @keydown.tab="onTab"
            >
                <div
                    class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"
                    @click="onCancel"
                ></div>

                <div
                    ref="panelRef"
                    :class="['relative w-full bg-white rounded-2xl shadow-xl flex flex-col max-h-[90vh] focus:outline-none', sizeClass]"
                    tabindex="-1"
                >
                    <!-- Header -->
                    <div class="flex items-start justify-between px-6 py-4 border-b border-slate-200 gap-4">
                        <div class="min-w-0 flex-1">
                            <h2 :id="titleId" class="text-base font-semibold text-slate-900 leading-tight">
                                {{ title }}
                            </h2>
                            <p
                                v-if="description"
                                :id="descriptionId"
                                class="mt-1 text-sm text-slate-500"
                            >
                                {{ description }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="shrink-0 text-slate-400 hover:text-slate-600 transition-colors p-1 rounded-md hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                            aria-label="Close dialog"
                            @click="onCancel"
                        >
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <form class="flex flex-col flex-1 min-h-0" @submit.prevent="onSubmit">
                        <div class="flex-1 overflow-y-auto px-6 py-5">
                            <slot></slot>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl flex items-center justify-end gap-2">
                            <slot name="footer">
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
                                    @click="onCancel"
                                >
                                    {{ cancelLabel }}
                                </button>
                                <button
                                    type="submit"
                                    :disabled="loading"
                                    class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg bg-brand-600 text-white hover:bg-brand-700 active:bg-brand-800 disabled:bg-slate-300 disabled:cursor-not-allowed transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
                                >
                                    <svg v-if="loading" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                                    </svg>
                                    {{ submitLabel }}
                                </button>
                            </slot>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, nextTick, ref, useId, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, required: true },
    description: { type: String, default: '' },
    size: { type: String, default: 'lg' },
    submitLabel: { type: String, default: 'Save' },
    cancelLabel: { type: String, default: 'Cancel' },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['submit', 'cancel', 'close']);

const sizeClass = computed(() => ({
    sm: 'max-w-md',
    md: 'max-w-lg',
    lg: 'max-w-2xl',
    xl: 'max-w-4xl',
}[props.size] || 'max-w-2xl'));

const panelRef = ref(null);
const titleId = useId();
const descriptionId = useId();

let previouslyFocused = null;

const FOCUSABLE_SELECTOR = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

function getFocusable() {
    if (!panelRef.value) return [];
    return Array.from(panelRef.value.querySelectorAll(FOCUSABLE_SELECTOR))
        .filter((el) => !el.hasAttribute('disabled') && el.offsetParent !== null);
}

function onTab(e) {
    const focusables = getFocusable();
    if (focusables.length === 0) return;
    const first = focusables[0];
    const last = focusables[focusables.length - 1];
    const active = document.activeElement;

    if (e.shiftKey) {
        if (active === first || !panelRef.value?.contains(active)) {
            e.preventDefault();
            last.focus();
        }
    } else {
        if (active === last) {
            e.preventDefault();
            first.focus();
        }
    }
}

function onSubmit() {
    emit('submit');
}

function onCancel() {
    if (props.loading) return;
    emit('cancel');
    emit('close');
}

function restoreFocus() {
    if (previouslyFocused && typeof previouslyFocused.focus === 'function') {
        previouslyFocused.focus();
    }
    previouslyFocused = null;
}

watch(
    () => props.show,
    async (val) => {
        if (val) {
            previouslyFocused = document.activeElement;
            document.body.style.overflow = 'hidden';
            await nextTick();
            const focusables = getFocusable();
            // Focus the panel first so screen readers announce the dialog,
            // then move to the first focusable element on the next tick.
            if (panelRef.value) panelRef.value.focus({ preventScroll: true });
            await nextTick();
            if (focusables.length > 0) {
                focusables[0].focus({ preventScroll: true });
            }
        } else {
            document.body.style.overflow = '';
            restoreFocus();
        }
    }
);
</script>
