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
    success: 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20',
    warning: 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20',
    danger: 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20',
    info: 'bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-600/20',
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
