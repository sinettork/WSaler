<template>
    <div class="space-y-6">
        <PageHeader :title="$t('nav.appSettings')" subtitle="Configure global application settings." />

        <BaseCard padding="lg">
            <div class="space-y-8 max-w-2xl">
                <section>
                    <h3 class="text-lg font-semibold text-slate-900 mb-4">{{ $t('common.general') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <BaseSelect
                            v-model="settings.locale"
                            name="locale"
                            :label="$t('common.language')"
                            :options="[
                                { value: 'en', label: 'English' },
                                { value: 'km', label: 'Khmer' },
                            ]"
                        />
                        <BaseSelect
                            v-model="settings.displayCurrency"
                            name="currency"
                            :label="$t('common.currency')"
                            :options="[
                                { value: 'USD', label: 'USD ($)' },
                                { value: 'KHR', label: 'KHR (៛)' },
                            ]"
                        />
                    </div>
                </section>

                <section>
                    <h3 class="text-lg font-semibold text-slate-900 mb-4">{{ $t('common.exchange_rate') }}</h3>
                    <BaseInput
                        v-model.number="settings.exchangeRate"
                        name="exchangeRate"
                        :label="$t('common.exchange_rate')"
                        placeholder="4100"
                        type="number"
                        min="1"
                        step="1"
                        @blur="settings.setExchangeRate(settings.exchangeRate)"
                    />
                    <p class="text-sm text-slate-500 mt-1">1 USD = {{ settings.exchangeRate }} KHR</p>
                </section>

                <div class="pt-4 border-t border-slate-200">
                    <BaseButton @click="saveSettings" variant="primary">{{ $t('common.save') }}</BaseButton>
                </div>
            </div>
        </BaseCard>
    </div>
</template>

<script setup>
import { useSettingsStore } from '@/stores/settings';
import { useToastStore } from '@/stores/toast';
import BaseCard from '@/components/ui/BaseCard.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import PageHeader from '@/components/ui/PageHeader.vue';

const settings = useSettingsStore();
const toast = useToastStore();

async function saveSettings() {
    settings.persist();
    toast.success('Settings saved successfully');
}
</script>
