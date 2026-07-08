import i18next from 'i18next';
import LanguageDetector from 'i18next-browser-languagedetector';
import en from './locales/en.json';
import km from './locales/km.json';

// Module top-level: only safe side-effects (no Pinia reads here).
i18next
    .use(LanguageDetector)
    .init({
        resources: {
            en: { translation: en },
            km: { translation: km },
        },
        fallbackLng: 'en',
        supportedLngs: ['en', 'km'],
        interpolation: { escapeValue: false },
        detection: {
            order: ['localStorage', 'navigator'],
            lookupLocalStorage: 'wsaler.locale',
            caches: ['localStorage'],
        },
    });

export function setI18nLocale(loc) {
    if (!['en', 'km'].includes(loc)) loc = 'en';
    i18next.changeLanguage(loc);
    document.documentElement.lang = loc;
}

export default i18next;
