import './bootstrap';
import '../css/app.css';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import i18n, { setI18nLocale } from '@/i18n';
import { useSettingsStore } from '@/stores/settings';

const app = createApp(App);
app.use(createPinia());           // Pinia installed FIRST

// Now safe to read the settings store.
const settings = useSettingsStore();
setI18nLocale(settings.locale);

// Register $t as a global property so templates can use {{ $t('key') }}.
app.config.globalProperties.$t = (key, opts) => i18n.t(key, opts);
// Optional: make i18n available without explicit import in every component.
app.config.globalProperties.$i18n = { t: (k, o) => i18n.t(k, o) };

app.use(router);
app.mount('#app');
