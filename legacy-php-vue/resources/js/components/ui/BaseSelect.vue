<script setup>
import { computed, useId } from 'vue';

const modelValue = defineModel({ type: [String, Number], default: '' });

const props = defineProps({
    label: { type: String, default: '' },
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: '' },
    error: { type: String, default: '' },
    hint: { type: String, default: '' },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    id: { type: String, default: '' },
    name: { type: String, default: '' },
});

const autoId = useId();
const inputId = computed(() => props.id || `base-select-${autoId}`);

const normalizedOptions = computed(() =>
    props.options.map((opt) =>
        typeof opt === 'object' && opt !== null
            ? { value: opt.value, label: opt.label }
            : { value: opt, label: String(opt) }
    )
);

const selectClasses = computed(() => [
    'block w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:outline-none focus:ring-2 disabled:bg-slate-50',
    props.error
        ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-500'
        : 'border-slate-300 focus:border-brand-500 focus:ring-brand-500',
]);

function onChange(e) {
    const raw = e.target.value;
    const match = normalizedOptions.value.find((o) => String(o.value) === raw);
    modelValue.value = match ? match.value : raw;
}
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

        <select
            :id="inputId"
            :name="name || undefined"
            :required="required"
            :disabled="disabled"
            :aria-invalid="error ? 'true' : undefined"
            :aria-describedby="error ? `${inputId}-error` : hint ? `${inputId}-hint` : undefined"
            :class="selectClasses"
            @change="onChange"
        >
            <option
                v-if="placeholder"
                value=""
                disabled
                selected
            >
                {{ placeholder }}
            </option>

            <option
                v-for="opt in normalizedOptions"
                :key="String(opt.value)"
                :value="String(opt.value)"
                :selected="String(opt.value) === String(modelValue)"
            >
                {{ opt.label }}
            </option>
        </select>

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
