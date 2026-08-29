<template>
    <div class="space-y-6">
        <div v-if="store.loading && editing" class="flex justify-center py-16">
            <Spinner size="lg" label="Loading unit..." />
        </div>

        <form v-else @submit.prevent="save" novalidate class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div class="min-w-0">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-2"
                        @click="$router.push('/units')"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Back to units
                    </button>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                        {{ editing ? `Update unit: ${form.name || '\u2014'}` : 'Create unit of measure' }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ editing
                            ? 'Change the name, short code, or conversion factor for this unit.'
                            : 'Add a new unit. Mark it as base if it is the reference unit for stock calculations.' }}
                    </p>
                </div>
            </div>

            <BaseCard>
                <template #header>
                    <h2 class="text-sm font-semibold text-slate-900">Unit details</h2>
                </template>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <BaseInput
                            v-model="form.name"
                            name="name"
                            label="Name"
                            placeholder="e.g. Kilogram"
                            required
                            :error="errors.name"
                            @input="clearError('name')"
                        />
                        <BaseInput
                            v-model="form.short_code"
                            name="short_code"
                            label="Short code"
                            placeholder="e.g. kg"
                            required
                            :error="errors.short_code"
                            hint="Short identifier shown next to quantities."
                            @input="clearError('short_code')"
                        />
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer pt-2 border-t border-slate-100">
                        <input
                            v-model="form.base"
                            type="checkbox"
                            class="w-4 h-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                        />
                        <span class="text-sm text-slate-700">Base unit &mdash; the reference unit for stock calculations</span>
                    </label>
                    <BaseInput
                        v-model.number="form.conversion_factor_to_base"
                        name="conversion_factor_to_base"
                        type="number"
                        label="Conversion factor to base"
                        hint="How many base units equal one of this unit. Ignored for base units."
                        :disabled="form.base"
                        :error="errors.conversion_factor_to_base"
                        @input="clearError('conversion_factor_to_base')"
                    />
                </div>
            </BaseCard>

            <div class="flex items-center justify-end gap-2 pt-2">
                <BaseButton type="button" variant="secondary" @click="$router.push('/units')">
                    Cancel
                </BaseButton>
                <BaseButton type="submit" :loading="store.loading">
                    {{ editing ? 'Save changes' : 'Create unit' }}
                </BaseButton>
            </div>
        </form>
    </div>
</template>

<script setup>
import { reactive, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useUnitsStore } from '@/stores/units';
import { useToastStore } from '@/stores/toast';
import { useFormErrors } from '@/composables/useFormErrors';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseCard from '@/components/ui/BaseCard.vue';
import Spinner from '@/components/ui/Spinner.vue';

const route = useRoute();
const router = useRouter();
const store = useUnitsStore();
const toast = useToastStore();
const errors = reactive({});
const { focusFirstError, clearErrors, clearError } = useFormErrors(errors);

const editing = computed(() => !!route.params.id);

const form = reactive({
    id: null,
    name: '',
    short_code: '',
    base: false,
    conversion_factor_to_base: 1,
});

async function save() {
    clearErrors();
    try {
        const payload = { ...form };
        if (editing.value) {
            await store.update(form.id, payload);
            toast.success('Unit updated.');
        } else {
            delete payload.id;
            await store.create(payload);
            toast.success('Unit created.');
        }
        router.push('/units');
    } catch (e) {
        if (e.fieldErrors) {
            Object.assign(errors, e.fieldErrors);
            focusFirstError();
        }
    }
}

onMounted(async () => {
    if (editing.value) {
        const row = await store.show(route.params.id);
        const data = row?.data || row;
        if (data) {
            Object.assign(form, {
                id: data.id,
                name: data.name || '',
                short_code: data.short_code || '',
                base: !!data.base,
                conversion_factor_to_base: data.conversion_factor_to_base ?? 1,
            });
        }
    }
});
</script>
