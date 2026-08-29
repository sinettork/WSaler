import { ref } from 'vue';
import i18next from '@/i18n';

const ready = ref(i18next.isInitialized);

i18next.on('initialized', () => {
    ready.value = true;
});
i18next.on('languageChanged', () => {
    ready.value = true;
});

export function useI18n() {
    return {
        t: (key, opts) => i18next.t(key, opts),
        locale: i18next.language,
        ready,
    };
}
