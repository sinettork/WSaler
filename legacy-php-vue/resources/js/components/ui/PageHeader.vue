<script setup>
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    eyebrow: { type: String, default: '' },
});
</script>

<template>
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div class="min-w-0">
            <p
                v-if="eyebrow"
                class="text-xs font-semibold uppercase tracking-wider text-brand-600 mb-1"
            >
                {{ eyebrow }}
            </p>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                {{ title }}
            </h1>
            <p v-if="subtitle" class="mt-1 text-sm text-slate-500">
                {{ subtitle }}
            </p>
        </div>

        <div v-if="$slots.actions" class="flex items-center gap-2 shrink-0">
            <slot name="actions" />
        </div>
    </div>
</template>
