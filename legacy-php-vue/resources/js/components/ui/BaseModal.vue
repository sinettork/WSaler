<script setup>
import { computed, ref, watch } from 'vue';

const modelValue = defineModel({ type: Boolean, default: false });

const props = defineProps({
    title: { type: String, default: '' },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md', 'lg', 'xl'].includes(v),
    },
});

const dialogRef = ref(null);

const sizeClass = computed(() => {
    return {
        sm: 'max-w-sm',
        md: 'max-w-md',
        lg: 'max-w-lg',
        xl: 'max-w-2xl',
    }[props.size];
});

function open() {
    if (dialogRef.value && !dialogRef.value.open) {
        dialogRef.value.showModal();
    }
}

function close() {
    if (dialogRef.value && dialogRef.value.open) {
        dialogRef.value.close();
    }
    if (modelValue.value) {
        modelValue.value = false;
    }
}

function onBackdropClick(e) {
    if (e.target === dialogRef.value) {
        close();
    }
}

function onDialogClose() {
    if (modelValue.value) {
        modelValue.value = false;
    }
}

watch(modelValue, (val) => {
    if (val) {
        // wait for next tick so ref is mounted
        queueMicrotask(open);
    } else {
        close();
    }
});
</script>

<template>
    <dialog
        ref="dialogRef"
        :class="['rounded-2xl shadow-2xl p-0 w-full backdrop:bg-slate-900/50', sizeClass]"
        @click="onBackdropClick"
        @close="onDialogClose"
    >
        <div class="flex flex-col max-h-[90vh]">
            <div
                v-if="title || $slots.header"
                class="px-6 py-4 border-b border-slate-200 flex items-center justify-between"
            >
                <h3 class="text-base font-semibold text-slate-900">
                    <slot name="header">{{ title }}</slot>
                </h3>
                <button
                    type="button"
                    class="text-slate-400 hover:text-slate-600 transition-colors"
                    aria-label="Close"
                    @click="close"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            <div class="px-6 py-4 text-sm text-slate-700 overflow-auto">
                <slot />
            </div>

            <div
                v-if="$slots.footer"
                class="px-6 py-3 border-t border-slate-200 bg-slate-50 flex justify-end gap-2"
            >
                <slot name="footer" />
            </div>
        </div>
    </dialog>
</template>
