<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: { type: String, default: '' },
    size: { type: String, default: 'md' }, // 'sm' | 'md' | 'lg'
    center: { type: Boolean, default: false },
});

const sizeClass = computed(() => ({
    sm: 'h-4 w-4',
    md: 'h-8 w-8',
    lg: 'h-12 w-12',
}[props.size] || 'h-8 w-8'));
</script>

<template>
    <div
        :class="['flex items-center gap-3 text-slate-500', center ? 'justify-center py-12' : '']"
        role="status"
        :aria-label="label || 'Loading'"
    >
        <svg
            :class="[sizeClass, 'animate-spin text-brand-600']"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
        </svg>
        <span v-if="label" class="text-sm">{{ label }}</span>
    </div>
</template>
