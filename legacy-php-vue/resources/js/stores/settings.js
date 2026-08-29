import { defineStore } from 'pinia';

const STORAGE_KEY = 'wsaler.settings.v1';

function load() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return null;
        return JSON.parse(raw);
    } catch {
        return null;
    }
}

export const useSettingsStore = defineStore('settings', {
    state: () => {
        const saved = load() || {};
        return {
            locale: saved.locale || 'en',
            displayCurrency: saved.displayCurrency || 'USD',
            exchangeRate: Number(saved.exchangeRate) || 4100,
        };
    },
    actions: {
        setLocale(loc) {
            this.locale = ['en', 'km'].includes(loc) ? loc : 'en';
            this.persist();
        },
        setCurrency(c) {
            this.displayCurrency = ['USD', 'KHR'].includes(c) ? c : 'USD';
            this.persist();
        },
        setExchangeRate(r) {
            const n = Number(r);
            if (Number.isFinite(n) && n > 0) {
                this.exchangeRate = n;
                this.persist();
            }
        },
        persist() {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({
                locale: this.locale,
                displayCurrency: this.displayCurrency,
                exchangeRate: this.exchangeRate,
            }));
        },
    },
});
