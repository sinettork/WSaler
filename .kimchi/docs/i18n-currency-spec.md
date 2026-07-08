# i18n + Multi-Currency Support — Implementation Plan

## Goal

Add Khmer (ខ្មែរ) language support with i18next and seamless USD/KHR currency display, using Google Sans font for Khmer glyphs. This is a cross-cutting frontend enhancement that touches the Vue SPA only — no backend changes.

## Non-Goals

- Full UI translation of every label. Scope = nav, sidebar, key buttons ("Logout", "Save", "Cancel", "Search…", "Complete sale"), currency symbol/formatting, and the POS / Sales / Products display strings. Other strings remain in English.
- Per-tenant currency configuration or backend currency endpoints.
- Currency-amount storage changes. All amounts remain stored in USD (the existing single base currency); display converts via an exchange rate held in the frontend settings store.

## Decisions

| Decision | Choice | Reason |
|---|---|---|
| Library | `i18next` + `i18next-browser-languagedetector` (used directly, not bridged through vue-i18n) | User explicitly asked for i18next; vue-i18n does NOT consume the i18next instance at runtime — using i18next directly means the installed package is the one doing translation |
| Vue binding | Thin `useI18n()` composable + `app.config.globalProperties.$t` so templates can write `{{ $t('nav.dashboard') }}` | Idiomatic Vue 3 + i18next without a second framework |
| Pinia init order | Settings store is read AFTER `app.use(createPinia())`, never at module-load | Calling `useSettingsStore()` before Pinia is installed emits warnings and creates a detached store |
| Base currency storage | USD (unchanged) | All amounts in DB already in USD; changing storage is out of scope |
| Display currency | User preference (USD or KHR) persisted in `localStorage` | Per-user UI choice; safe default = USD |
| Exchange rate | Configurable in `localStorage` via settings store, default `4100` KHR = 1 USD | Round-number default close to current market rate; user can edit |
| KHR formatting | `៛` symbol, suffix position, 0 decimals, thousands separator | Khmer Riel is conventionally written without decimal subunits; symbol goes after the number in Khmer usage |
| USD formatting | `$` symbol, prefix position, 2 decimals, thousands separator | Matches existing behaviour |
| Font | Google Sans from Google Fonts CDN, loaded via `<link>` tag in the SPA shell `<head>`, with `Noto Sans Khmer` fallback | Google Sans has Khmer glyphs; loading via `<link>` (not CSS `@import`) starts the fetch in parallel with JS bundle — eliminates FOUT. Noto Sans Khmer guarantees coverage if Google Sans load fails |
| Font fallback for non-Khmer | Keep existing `"Instrument Sans"` stack | Don't regress the rest of the UI |

## File Map

### New files
| Path | Purpose |
|---|---|
| `resources/js/i18n/index.js` | i18next init with `en` + `km` resources; vue-i18n instance; locale persistence |
| `resources/js/i18n/locales/en.json` | English strings |
| `resources/js/i18n/locales/km.json` | Khmer strings (mirror keys, Khmer values) |
| `resources/js/stores/settings.js` | Pinia store: `locale`, `displayCurrency`, `exchangeRate`, with localStorage persistence |
| `resources/js/composables/useCurrency.js` | `formatMoney(value)`, `formatMoneyNumber(value)` (raw number, no symbol), `convert(value)` helpers |

### Modified files
| Path | Change |
|---|---|
| `package.json` | Add deps: `i18next`, `i18next-browser-languagedetector` |
| `resources/views/welcome.blade.php` (or whichever SPA shell renders `#app`) | Add `<link rel="preconnect">` + `<link rel="stylesheet">` to Google Fonts in `<head>` so fonts start loading before JS bundle parses |
| `resources/js/app.js` | Install Pinia BEFORE reading the settings store; register `i18n.init()` after Pinia; register `$t` global property |
| `resources/css/app.css` | Update `--font-sans` to include Google Sans + Noto Sans Khmer; add `:lang(km)` rule |
| `resources/js/components/AppNavbar.vue` | Add language switcher + currency switcher dropdowns; translate "Search products…", "Logout"; on language click call BOTH `settings.setLocale(loc)` AND `setI18nLocale(loc)` |
| `resources/js/components/AppSidebar.vue` | Translate nav labels via `$t()` |
| `resources/js/pages/sales/Pos.vue` | Drop local `formatCurrency`; use `useCurrency()` |
| `resources/js/pages/sales/PosReceipt.vue` | Same |
| `resources/js/pages/sales/SaleDetail.vue` | Same |
| `resources/js/pages/sales/SalesList.vue` | Same |
| `resources/js/pages/products/ProductList.vue` | Same |
| `resources/js/pages/products/ProductDetail.vue` | Same |

### Public API (contracts)

**`useCurrency()`** (`resources/js/composables/useCurrency.js`)
```js
import { computed } from 'vue';
import { useSettingsStore } from '@/stores/settings';

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
    const thousands = computed(() =>
        settings.locale === 'km' ? '.' : ','
    );
    const decimal = computed(() =>
        settings.locale === 'km' ? ',' : '.'
    );

    function convert(amountUsd) {
        const n = Number(amountUsd) || 0;
        return settings.displayCurrency === 'KHR'
            ? Math.round(n * settings.exchangeRate)
            : n;
    }

    function formatNumber(value, opts = {}) {
        const n = Number(value) || 0;
        const d = opts.decimals ?? decimals.value;
        const fixed = n.toFixed(d);
        const [int, frac] = fixed.split('.');
        const intGrouped = int.replace(/\B(?=(\d{3})+(?!\d))/g, thousands.value);
        const formatted = frac !== undefined
            ? intGrouped + decimal.value + frac
            : intGrouped;
        return { formatted, intPart: intGrouped, fracPart: frac ?? '' };
    }

    function formatMoney(amountUsd, opts = {}) {
        const n = convert(amountUsd);
        const { formatted } = formatNumber(n, opts);
        return position.value === 'suffix'
            ? `${formatted}${symbol.value}`
            : `${symbol.value}${formatted}`;
    }

    return { formatMoney, formatNumber, convert, symbol, position, decimals };
}
```

**`useSettingsStore()`** (`resources/js/stores/settings.js`)
```js
import { defineStore } from 'pinia';

const STORAGE_KEY = 'wsaler.settings.v1';

function load() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return null;
        return JSON.parse(raw);
    } catch { return null; }
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
```

**`resources/js/i18n/index.js`** — i18next is used directly. No vue-i18n.

```js
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
```

**`resources/js/composables/useI18n.js`** — Thin Vue 3 binding so templates can use `$t()` or `useI18n().t()`.

```js
import { ref } from 'vue';
import i18next from '@/i18n';

const ready = ref(i18next.isInitialized);

i18next.on('initialized', () => { ready.value = true; });
i18next.on('languageChanged', () => { ready.value = true; });

export function useI18n() {
    return {
        t: (key, opts) => i18next.t(key, opts),
        locale: i18next.language,
        ready,
    };
}
```

**`resources/js/app.js`** (modified) — Pinia is installed BEFORE the settings store is read.

```js
import './bootstrap';
import '../css/app.css';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import i18n, { setI18nLocale } from '@/i18n';
import { useSettingsStore } from '@/stores/settings';
import { useI18n } from '@/composables/useI18n';

const app = createApp(App);
app.use(createPinia());           // Pinia installed FIRST

// Now safe to read the settings store.
const settings = useSettingsStore();
setI18nLocale(settings.locale);

// Register $t as a global property so templates can use {{ $t('key') }}.
app.config.globalProperties.$t = (key, opts) => i18n.t(key, opts);
// Optional: make useI18n available without explicit import in every component.
app.config.globalProperties.$i18n = { t: (k, o) => i18n.t(k, o) };

app.use(router);
app.mount('#app');
```

## Locale JSON keys (initial set)

**`en.json`**
```json
{
    "nav": {
        "dashboard": "Dashboard",
        "pos": "Point of Sale",
        "sales": "Sales",
        "products": "Products",
        "batches": "Batches",
        "units": "Units",
        "warehouses": "Warehouses",
        "master": "Master Data",
        "categories": "Categories",
        "brands": "Brands",
        "suppliers": "Suppliers",
        "customers": "Customers",
        "admin": "Administration",
        "users": "Users"
    },
    "common": {
        "search_products": "Search products…",
        "logout": "Logout",
        "save": "Save",
        "cancel": "Cancel",
        "delete": "Delete",
        "edit": "Edit",
        "complete_sale": "Complete sale",
        "language": "Language",
        "currency": "Currency",
        "exchange_rate": "Exchange rate (1 USD = ? KHR)"
    }
}
```

**`km.json`**
```json
{
    "nav": {
        "dashboard": "ផ្ទាំងគ្រប់គ្រង",
        "pos": "កន្លែងលក់",
        "sales": "ការលក់",
        "products": "ផលិតផល",
        "batches": "បាច់ទំនិញ",
        "units": "ឯកតា",
        "warehouses": "ឃ្លាំង",
        "master": "ទិន្នន័យមេ",
        "categories": "ប្រភេទ",
        "brands": "ម៉ាក",
        "suppliers": "អ្នកផ្គត់ផ្គង់",
        "customers": "អតិថិជន",
        "admin": "រដ្ឋបាល",
        "users": "អ្នកប្រើប្រាស់"
    },
    "common": {
        "search_products": "ស្វែងរកផលិតផល…",
        "logout": "ចាកចេញ",
        "save": "រក្សាទុក",
        "cancel": "បោះបង់",
        "delete": "លុប",
        "edit": "កែសម្រួល",
        "complete_sale": "បញ្ចប់ការលក់",
        "language": "ភាសា",
        "currency": "រូបិយប័ណ្ណ",
        "exchange_rate": "អត្រាប្តូរប្រាក់ (1 USD = ? KHR)"
    }
}
```

## CSS update (`resources/css/app.css`)

The Google Fonts `<link>` is added in the SPA shell's `<head>` (see File Map), not as a CSS `@import`. This lets the browser start fetching font files in parallel with the JS bundle and avoids FOUT. The CSS just declares the font stack.

```css
@theme {
  --font-sans: "Google Sans", "Instrument Sans", "Noto Sans Khmer", ui-sans-serif, system-ui, sans-serif;
  /* … existing colors unchanged … */
}

@layer base {
  html:lang(km) {
    font-family: "Google Sans", "Noto Sans Khmer", "Instrument Sans", ui-sans-serif, system-ui, sans-serif;
  }
}
```

**SPA shell `<head>` addition** (`resources/views/welcome.blade.php` — verify exact filename in the project):
```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;600;700&family=Noto+Sans+Khmer:wght@400;500;600;700&display=swap">
```

If the SPA shell blade doesn't exist or isn't loaded, fall back to a CSS `@import url(...)` at the top of `app.css` (slightly slower to start fetching but functionally equivalent).

## Navbar switchers

Two small dropdowns in `AppNavbar.vue`, right of the notification bell, before the user menu:
- **Language**: 🌐 EN / ខ្មែរ — click handler calls BOTH `settings.setLocale(loc)` AND `setI18nLocale(loc)`. The store update alone does NOT trigger i18next to re-render; the explicit `setI18nLocale` call is required.
- **Currency**: $ USD / ៛ KHR — calls `settings.setCurrency()`. Vue reactivity then updates every `formatMoney()` call site automatically.

Hover-to-open, click-to-select. No new dependencies.

## Edge cases — `formatMoney` defensive behaviour

The composable handles every input as `Number(value) || 0`, so:
- `formatMoney(0)` → `"$0.00"` (USD) or `"៛0"` (KHR)
- `formatMoney(null)` → `"$0.00"` (USD) or `"៛0"` (KHR)
- `formatMoney(NaN)` → `"$0.00"` (USD) or `"៛0"` (KHR)
- `formatMoney(undefined)` → `"$0.00"` (USD) or `"៛0"` (KHR)
- `formatMoney(-1234.56)` → `"-$1,234.56"` (USD) — negative sign goes before the symbol; KHR rounding takes the absolute then re-applies sign: `"-៛5,061,696"`

Verification will assert `formatMoney(0)` returns `"$0.00"` with default settings.

## Test Strategy

No existing frontend test runner in `package.json` (only PHP tests via phpunit). Adding Vitest is out of scope for this change. Verification will be:
1. `npm run build` — succeeds (vite + vue + tailwind all compile)
2. Manual visual smoke (documented in the spec): switching language changes sidebar text, switching currency changes `$1,234.56` → `៛5,058,000`
3. Existing PHP tests still pass (`composer test`)

If the user later asks for frontend tests, add Vitest in a follow-up chunk.

## Chunks

The work decomposes into **3 sequential chunks**. All are `simple` (no concurrency primitives; just Vue composables, JSON, CSS, and straight call-site replacements).

### Chunk 1 — Foundation (deps + i18n + settings + currency composable + CSS font + SPA shell link)
- Files: `package.json`, `resources/js/i18n/index.js`, `resources/js/i18n/locales/en.json`, `resources/js/i18n/locales/km.json`, `resources/js/composables/useI18n.js`, `resources/js/stores/settings.js`, `resources/js/composables/useCurrency.js`, `resources/js/app.js`, `resources/css/app.css`, `resources/views/welcome.blade.php` (or whatever SPA shell renders `#app`)
- Acceptance:
  - `npm install && npm run build` exits 0
  - `useSettingsStore` is constructible after `app.use(createPinia())`
  - `useCurrency().formatMoney(1234.56)` returns `"$1,234.56"` with default settings
  - `useCurrency().formatMoney(1234.56)` returns `"៛5,061,696"` when `displayCurrency='KHR'` and `exchangeRate=4100` (note: 1234.56 × 4100 = 5,061,696)
  - `useCurrency().formatMoney(0)` returns `"$0.00"`; `formatMoney(null)` → `"$0.00"`; `formatMoney(NaN)` → `"$0.00"`; `formatMoney(-50)` → `"-$50.00"`
  - `i18next.t('nav.dashboard')` returns `"Dashboard"` after init; `'ផ្ទាំងគ្រប់គ្រង'` when locale is `'km'`
  - `<html lang>` attribute reflects the persisted locale on page load

### Chunk 2 — Navbar + Sidebar switchers and translations
- Files: `resources/js/components/AppNavbar.vue`, `resources/js/components/AppSidebar.vue`
- Acceptance:
  - Language switcher renders with two options (EN, ខ្មែរ)
  - Clicking EN sets `html.lang = "en"` and updates sidebar text to English
  - Clicking ខ្មែរ sets `html.lang = "km"` and updates sidebar text to Khmer script
  - Language click handler calls BOTH `settings.setLocale(loc)` AND `setI18nLocale(loc)` (verify by reading the click handler code)
  - Currency switcher renders with two options (USD, KHR)
  - Clicking USD/KHR updates `settings.displayCurrency` (verify by reading the click handler code; visual effect on currency formatting happens once Chunk 3 lands)
  - No console errors when switching either dropdown

### Chunk 3 — Replace formatCurrency call sites
- Files: `resources/js/pages/sales/Pos.vue`, `resources/js/pages/sales/PosReceipt.vue`, `resources/js/pages/sales/SaleDetail.vue`, `resources/js/pages/sales/SalesList.vue`, `resources/js/pages/products/ProductList.vue`, `resources/js/pages/products/ProductDetail.vue`
- Each file: delete the local `function formatCurrency(value)`, add `import { useCurrency } from '@/composables/useCurrency'` and `const { formatMoney } = useCurrency()` (or destructure inside `<script setup>`). Replace `{{ formatCurrency(x) }}` → `{{ formatMoney(x) }}`.
- Acceptance:
  - `npm run build` exits 0
  - `grep -rn 'function formatCurrency' resources/js` returns nothing
  - Every rendered currency uses `formatMoney`
  - With displayCurrency=KHR, navigating to `/sales` shows totals as `៛…`; with USD, shows as `$…`

## Acceptance criteria for the whole change

1. `npm install` adds `i18next` and `i18next-browser-languagedetector`; both appear in `package.json` dependencies. `vue-i18n` is NOT added.
2. `npm run build` exits 0.
3. Selecting Khmer in the navbar updates `<html lang="km">` and renders Khmer script in the sidebar.
4. Selecting KHR in the navbar changes all visible `$1,234.56` to `៛5,061,696` (using the default 4100 rate).
5. Both selections persist across page reload (localStorage).
6. No component still defines its own `function formatCurrency` — all currency formatting routes through `useCurrency()`.
7. Google Sans loads from Google Fonts; Khmer characters render in Google Sans glyphs (not fallback square boxes).
8. No new PHP files modified.
9. `i18next` is the actual translation engine at runtime: calling `i18next.t('nav.dashboard')` from the browser console returns the current locale's string (verifies the package is wired, not just installed).

## Risks

- **Google Sans CDN blocked**: User's network may block Google Fonts. Mitigation: `Noto Sans Khmer` is the explicit second item in the font stack, so Khmer glyphs always render even if Google Sans fails to load.
- **SSR / hydration**: This is a Vite SPA, not SSR — no hydration concern.
- **`localStorage` not available**: Settings store wraps reads in try/catch; defaults are used if storage is unavailable.
- **i18n init race**: Components mounted before i18n init could see `undefined` for `$t()`. Mitigation: i18n is initialised synchronously in `resources/js/i18n/index.js` (resources are imported at module load), and `createI18n` is given the messages directly. No `await` needed before mount.

## Out of scope (explicit)

- Backend currency conversion, persisted user locale per account
- Translating every label in the app (only nav, sidebar, common actions, and currency labels)
- Vitest / frontend test runner setup
- RTL support
- Number-input locale-aware parsing (only display is localised)
