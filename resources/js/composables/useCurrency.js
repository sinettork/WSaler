import { computed } from 'vue';
import { useSettingsStore } from '@/stores/settings';
import {
    convert as convertPure,
    formatNumber as formatNumberPure,
    formatMoneyPure,
} from './currencyHelpers';

// Re-export pure helpers so unit tests and other call sites can use them
// without depending on Pinia or Vue.
export { convertPure as convert, formatNumberPure as formatNumber, formatMoneyPure };

export function useCurrency() {
    const settings = useSettingsStore();

    const symbol = computed(() =>
        settings.displayCurrency === 'KHR' ? '៛' : '$'
    );
    const position = computed(() =>
        settings.displayCurrency === 'KHR' ? 'suffix' : 'prefix'
    );
    const decimals = computed(() =>
        settings.displayCurrency === 'KHR' ? 0 : 2
    );

    function convertWrapped(amountUsd) {
        return convertPure(amountUsd, settings);
    }

    function formatNumberWrapped(value, opts = {}) {
        return formatNumberPure(value, opts, settings);
    }

    function formatMoney(amountUsd, opts = {}) {
        return formatMoneyPure(amountUsd, opts, settings);
    }

    return {
        formatMoney,
        formatNumber: formatNumberWrapped,
        convert: convertWrapped,
        symbol,
        position,
        decimals,
    };
}
