<script setup>
import { computed, useSlots } from 'vue';

const props = defineProps({
    padding: {
        type: String,
        default: 'md',
        validator: (v) => ['none', 'sm', 'md', 'lg'].includes(v),
    },
    shadow: {
        type: String,
        default: 'sm',
        validator: (v) => ['none', 'sm', 'md'].includes(v),
    },
});

const slots = useSlots();

const shadowClass = computed(() => {
    if (props.shadow === 'md') return 'shadow-md';
    if (props.shadow === 'sm') return 'shadow-sm';
    return '';
});

const paddingClass = computed(() => {
    return {
        none: 'p-0',
        sm: 'p-3',
        md: 'p-5',
        lg: 'p-6',
    }[props.padding];
});

const hasHeader = computed(() => !!slots.header);
const hasFooter = computed(() => !!slots.footer);
</script>

<template>
    <div
        :class="['bg-white border border-slate-200 rounded-lg overflow-hidden', shadowClass]"
    >
        <div
            v-if="hasHeader"
            class="px-4 py-3 border-b border-slate-200 flex items-center justify-between"
        >
            <slot name="header" />
        </div>

        <div :class="paddingClass">
            <slot />
        </div>

        <div
            v-if="hasFooter"
            class="px-4 py-3 border-t border-slate-200 bg-slate-50"
        >
            <slot name="footer" />
        </div>
    </div>
</template>
