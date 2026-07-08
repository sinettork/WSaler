<template>
    <div ref="rootRef">
        <div class="flex items-end gap-2">
            <div class="flex-1 min-w-0">
                <slot name="field" />
            </div>
            <button
                type="button"
                class="mb-2 text-xs font-medium whitespace-nowrap inline-flex items-center gap-1 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-1 rounded px-1"
                :class="open ? 'text-slate-600 hover:text-slate-700' : 'text-brand-600 hover:text-brand-700'"
                :aria-expanded="open"
                :aria-label="open ? 'Cancel quick create' : `Create new ${label}`"
                @click="toggle"
            >
                <svg v-if="!open" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                {{ open ? 'Cancel' : 'New' }}
            </button>
        </div>

        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-1"
            @enter="onEnter"
        >
            <div
                v-if="open"
                class="mt-2 p-3 bg-slate-50 rounded-lg ring-1 ring-slate-200"
                @keydown.esc.stop="close"
            >
                <div class="space-y-2">
                    <slot />
                </div>
                <div class="flex justify-end mt-3">
                    <BaseButton
                        type="button"
                        size="sm"
                        :loading="loading"
                        @click="$emit('create')"
                    >
                        Create &amp; select
                    </BaseButton>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue';
import BaseButton from '@/components/ui/BaseButton.vue';

const props = defineProps({
    label: { type: String, default: '' },
    loading: { type: Boolean, default: false },
    open: { type: Boolean, default: false },
});

const emit = defineEmits(['create', 'update:open']);

const rootRef = ref(null);

function toggle() {
    emit('update:open', !props.open);
}

function close() {
    if (props.open) emit('update:open', false);
}

async function onEnter() {
    // After the transition completes, move focus to the first
    // focusable element inside the inline form so the user can
    // start typing immediately.
    await nextTick();
    const root = rootRef.value;
    if (!root) return;
    const focusable = root.querySelector(
        'input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled])'
    );
    if (focusable) focusable.focus();
}

// Keep Escape working even if focus has moved outside the panel.
function onGlobalKey(e) {
    if (props.open && e.key === 'Escape') {
        e.preventDefault();
        close();
    }
}

watch(
    () => props.open,
    (val) => {
        if (val) {
            document.addEventListener('keydown', onGlobalKey);
        } else {
            document.removeEventListener('keydown', onGlobalKey);
        }
    }
);

import { onBeforeUnmount } from 'vue';
onBeforeUnmount(() => document.removeEventListener('keydown', onGlobalKey));
</script>
