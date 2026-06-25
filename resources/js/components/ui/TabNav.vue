<script setup>
defineProps({
    tabs: { type: Array, required: true }, // [{ key, label, count? }]
    modelValue: { type: [String, Number, null], required: true },
});
defineEmits(['update:modelValue']);

function tabClasses(isActive) {
    return [
        'inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors whitespace-nowrap',
        isActive
            ? 'border-brand-600 text-brand-700'
            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
    ];
}

function badgeClasses(isActive) {
    return [
        'inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full text-xs font-semibold',
        isActive
            ? 'bg-brand-100 text-brand-700'
            : 'bg-slate-100 text-slate-600',
    ];
}
</script>

<template>
    <div class="border-b border-slate-200 mb-4">
        <nav class="flex overflow-x-auto" aria-label="Tabs">
            <button
                v-for="tab in tabs"
                :key="String(tab.key)"
                type="button"
                :class="tabClasses(modelValue === tab.key)"
                :aria-current="modelValue === tab.key ? 'page' : undefined"
                @click="$emit('update:modelValue', tab.key)"
            >
                {{ tab.label }}
                <span
                    v-if="tab.count != null"
                    :class="badgeClasses(modelValue === tab.key)"
                >
                    {{ tab.count }}
                </span>
            </button>
        </nav>
    </div>
</template>
