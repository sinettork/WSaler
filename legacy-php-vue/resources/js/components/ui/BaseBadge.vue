<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'neutral',
        validator: (v) =>
            ['neutral', 'success', 'warning', 'danger', 'info', 'brand'].includes(v),
    },
});

const variantClasses = {
    neutral: 'bg-slate-100 text-slate-700',
    success: 'bg-status-fresh-bg text-status-fresh ring-1 ring-inset ring-status-fresh/20',
    warning: 'bg-status-warning-bg text-status-warning ring-1 ring-inset ring-status-warning/20',
    danger: 'bg-status-critical-bg text-status-critical ring-1 ring-inset ring-status-critical/20',
    info: 'bg-status-info-bg text-status-info ring-1 ring-inset ring-status-info/20',
    brand: 'bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-600/20',
};

const classes = computed(() => [
    'inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium',
    variantClasses[props.variant],
]);
</script>

<template>
    <span :class="classes">
        <slot />
    </span>
</template>
