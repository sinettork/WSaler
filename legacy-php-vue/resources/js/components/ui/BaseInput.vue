<script setup>
import { computed, useId } from 'vue';

const modelValue = defineModel({ type: [String, Number], default: '' });

const props = defineProps({
    label: { type: String, default: '' },
    type: { type: String, default: 'text' },
    placeholder: { type: String, default: '' },
    error: { type: String, default: '' },
    hint: { type: String, default: '' },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    id: { type: String, default: '' },
    name: { type: String, default: '' },
});

const autoId = useId();
const inputId = computed(() => props.id || `base-input-${autoId}`);

const inputClasses = computed(() => [
    'block w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 shadow-sm transition focus:outline-none focus:ring-2 disabled:bg-slate-50 disabled:text-slate-500',
    props.error
        ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-500'
        : 'border-slate-300 focus:border-brand-500 focus:ring-brand-500',
]);
</script>

<template>
    <div class="block w-full">
        <label
            v-if="label"
            :for="inputId"
            class="block text-sm font-medium text-slate-700 mb-1"
        >
            {{ label }}
            <span v-if="required" class="text-rose-600" aria-hidden="true">*</span>
        </label>

        <input
            :id="inputId"
            :type="type"
            :name="name || undefined"
            :value="modelValue"
            :placeholder="placeholder"
            :required="required"
            :disabled="disabled"
            :aria-invalid="error ? 'true' : undefined"
            :aria-describedby="error ? `${inputId}-error` : hint ? `${inputId}-hint` : undefined"
            :class="inputClasses"
            @input="modelValue = $event.target.value"
        />

        <p
            v-if="hint && !error"
            :id="`${inputId}-hint`"
            class="mt-1 text-xs text-slate-500"
        >
            {{ hint }}
        </p>

        <p
            v-if="error"
            :id="`${inputId}-error`"
            class="mt-1 text-xs text-rose-600"
        >
            {{ error }}
        </p>
    </div>
</template>
