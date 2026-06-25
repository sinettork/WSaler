# Tailwind CSS UI Overhaul — Implementation Plan

## Goal

Replace the existing Bootstrap-based UI with a clean, modern Tailwind CSS v4 design system across the entire Vue 3 SPA. Vue, Pinia, Vue Router, and `@tailwindcss/vite` are already installed but unused — the current `app.scss` only imports Bootstrap, all components use Bootstrap utility classes (`.d-flex`, `.card`, `.row`, `.col-md-3`, `.nav-pills`, etc.), and `app.js` imports the Bootstrap JS bundle.

**Outcome:** A consistent, professional B2B dashboard look — slate/indigo palette, soft borders, subtle shadows, Inter-grade typography (using already-loaded `Instrument Sans`), accessible focus states, and a reusable component library.

---

## Design System

### Palette (defined in `@theme` for Tailwind v4)
- **Brand**: `indigo-600` (`#4f46e5`) for primary actions
- **Surface**: `white` cards on `slate-50` page background
- **Border**: `slate-200` (1px) for separators
- **Text**: `slate-900` headings, `slate-600` body, `slate-400` muted
- **Semantic**: `emerald-600` success, `amber-500` warning, `rose-600` danger, `sky-500` info
- **Sidebar**: `slate-900` (dark) with `slate-100` text — modern dashboard convention

### Typography
- `Instrument Sans` (already loaded via Bunny Fonts in `vite.config.js`)
- Sizes via Tailwind defaults (no custom scale)

### Spacing / radius / shadow
- Cards: `rounded-xl`, `shadow-sm`, `border border-slate-200`
- Buttons: `rounded-lg`, `px-4 py-2`, `font-medium`
- Inputs: `rounded-lg`, `border-slate-300`, `focus:ring-2 focus:ring-indigo-500`

### Motion
- 150–200ms ease for transitions
- Sidebar section collapse via `max-height` (same pattern as current code)

---

## File-by-File Changes

### 1. `resources/css/app.scss` — Tailwind config + base layer
- Remove `@import "bootstrap/scss/bootstrap"`
- Add `@import "tailwindcss";`
- Add `@theme { ... }` block with custom palette tokens (`--color-brand`, semantic colors, font family)
- Add `@layer base { body { @apply bg-slate-50 text-slate-700 antialiased; } }`
- Add `@layer components { ... }` for reusable form/table patterns (optional — prefer utility composition)

### 2. `resources/js/app.js` — drop Bootstrap JS
- Remove `import 'bootstrap';`
- Keep axios, Pinia, router, Vue mount unchanged
- Add `import './assets/app.css'` if needed for any global custom CSS

### 3. New design-system components in `resources/js/components/ui/`
Create a thin component library — each is a thin wrapper over Tailwind classes for consistency:

| Component | Props | Purpose |
|---|---|---|
| `BaseButton.vue` | `variant: 'primary'\|'secondary'\|'ghost'\|'danger'`, `size: 'sm'\|'md'`, `loading`, `icon` | Buttons |
| `BaseCard.vue` | default slot, `#header`, `#footer` slots | Card surfaces |
| `BaseInput.vue` | `label`, `error`, `type`, `modelValue` | Inputs (uses `defineModel`) |
| `BaseSelect.vue` | `label`, `error`, `options`, `modelValue` | Selects |
| `BaseBadge.vue` | `variant: 'success'\|'warning'\|'danger'\|'info'\|'neutral'` | Status badges |
| `BaseModal.vue` | `modelValue`, `title`, `size` | Modal dialog (uses `<dialog>` + Teleport) |
| `BaseTable.vue` | `#head` slot, default slot for `<tbody>` | Consistent table styling |

### 4. Refactor layout shell — `resources/js/components/`
- **`AppLayout.vue`** — Replace `d-flex vh-100 overflow-hidden` with `flex h-screen overflow-hidden`. Replace `bg-body-tertiary` with `bg-slate-50`. Add a top header bar with breadcrumbs/page title placeholder.
- **`AppSidebar.vue`** — Convert to dark sidebar (`bg-slate-900 text-slate-100`), keep collapse logic. Add SVG icons (inline) for each section. Active state: `bg-slate-800 text-white`. Section toggle: uppercase tracking-wider text-slate-400.
- **`AppNavbar.vue`** — User menu (avatar circle + dropdown), search input on left, notification bell placeholder, logout. Replace Bootstrap classes.
- **`ToastContainer.vue`** — Fixed top-right, slide-in transition, semantic colors per toast type.

### 5. Refactor public pages
- **`pages/Login.vue`** — Centered card on full-bleed gradient background (`bg-gradient-to-br from-indigo-600 to-slate-900`), brand mark, email/password inputs, submit button.
- **`pages/Register.vue`** — Same shell as Login.
- **`pages/NotFound.vue`** — Large 404, illustration, "Back to Dashboard" link.

### 6. Refactor authenticated pages
- **`pages/Dashboard.vue`** — 4-column stat cards (Today Sales / Monthly Revenue / Low Stock / Near Expiry), each with icon, label, value, delta indicator. Use BaseCard + BaseBadge.
- **Master data pages** (`master/Categories.vue`, `Brands.vue`, `Suppliers.vue`, `Customers.vue`) — Refactor any visible Bootstrap classes to Tailwind; use BaseTable + BaseButton + BaseBadge for consistency.
- **`pages/admin/Users.vue`** — Same pattern.
- **`pages/products/*.vue`** — Same pattern.
- **`DataTable.vue` and `FormModal.vue`** — Rewrite to use BaseTable and BaseModal as foundation.

> **Scope note for pages**: The master/product pages share patterns (list + create/edit modal). We'll refactor the visible template classes to Tailwind and replace any Bootstrap-specific JS (`bootstrap.Modal`) with a custom `<dialog>`-based modal. If a page uses deep Bootstrap-specific features (e.g., `bootstrap.Dropdown`), we'll replace with a lightweight Vue-native equivalent.

---

## Build Chunks

Each chunk is independent, has tests/coverage check, and can be built by a standard-tier Builder.

### Chunk A — Tailwind foundation (simple)
**Files:**
- `resources/css/app.scss` (rewrite)
- `resources/js/app.js` (drop Bootstrap JS import)

**Acceptance:**
- `npm run build` succeeds
- Browser shows: `bg-slate-50` body, no Bootstrap CSS leaking in (verify via inspection — no `.btn`, `.card` classes needed)
- Tailwind utility classes (`text-slate-700`, `flex`, etc.) work

### Chunk B — UI primitive components (simple)
**Files (all new):**
- `resources/js/components/ui/BaseButton.vue`
- `resources/js/components/ui/BaseCard.vue`
- `resources/js/components/ui/BaseInput.vue`
- `resources/js/components/ui/BaseSelect.vue`
- `resources/js/components/ui/BaseBadge.vue`
- `resources/js/components/ui/BaseModal.vue`
- `resources/js/components/ui/BaseTable.vue`

Each file also gets an inline `*.test.js` smoke test (Vitest not yet installed — skip unit tests for now, verify visually via build). Alternative: write a single `resources/js/components/ui/__demo.vue` page that renders one of each to verify visually.

**Acceptance:**
- All components render without errors when imported into `App.vue`
- `BaseModal` opens/closes via `v-model`
- `BaseInput` v-model two-way binding works
- Storybook-style demo page (`resources/js/pages/__UiDemo.vue`) renders every primitive; route registered in dev only (or removed after verification)

### Chunk C — Layout shell refactor (simple)
**Files:**
- `resources/js/components/AppLayout.vue`
- `resources/js/components/AppSidebar.vue`
- `resources/js/components/AppNavbar.vue`
- `resources/js/components/ToastContainer.vue`

**Acceptance:**
- Sidebar collapses sections (existing logic preserved)
- Active route highlighting works
- Toast displays top-right with semantic colors
- Layout fills viewport, no scrollbar on body

### Chunk D — Public pages (simple)
**Files:**
- `resources/js/pages/Login.vue`
- `resources/js/pages/Register.vue`
- `resources/js/pages/NotFound.vue`

**Acceptance:**
- Login form submits (existing logic preserved)
- 404 page shows centered message + back link
- Mobile responsive (centered card, max-width)

### Chunk E — Authenticated pages refactor (simple)
**Files:**
- `resources/js/pages/Dashboard.vue`
- `resources/js/pages/admin/Users.vue`
- `resources/js/pages/master/Categories.vue`
- `resources/js/pages/master/Brands.vue`
- `resources/js/pages/master/Suppliers.vue`
- `resources/js/pages/master/Customers.vue`
- `resources/js/pages/products/ProductList.vue`
- `resources/js/pages/products/ProductForm.vue`
- `resources/js/pages/products/ProductDetail.vue`
- `resources/js/pages/products/BatchList.vue`
- `resources/js/pages/products/UnitList.vue`
- `resources/js/pages/products/WarehouseList.vue`
- `resources/js/components/DataTable.vue`
- `resources/js/components/FormModal.vue`

**Acceptance:**
- Dashboard renders 4 stat cards with icons
- Each list page shows data via BaseTable
- Each form page uses BaseInput/BaseSelect
- Modals (create/edit) use BaseModal

### Chunk F — Final verification & polish (simple)
**Files:** None new; verify build, run dev server, smoke-test all routes.

**Acceptance:**
- `npm run build` exits 0
- `npm run dev` starts cleanly
- All 13+ routes render without console errors
- No Bootstrap CSS classes appear in compiled output (`grep -r 'class=".*btn\|card\|row\|col-' resources/js` returns nothing or only Tailwind-context uses)

---

## Verification Strategy

1. After each chunk, the build agent runs `npm run build` to verify compilation.
2. After chunks B–E, the build agent inspects the produced HTML via `npm run dev` (or a static render) to confirm Tailwind classes apply.
3. Final smoke: orchestrator agent (via Fixer in review phase) loads each page route and checks for visual regressions.

**Why no unit tests:** The project has no existing JS test setup (Vitest/Jest not installed). Adding one for this refactor would inflate scope. Visual + build verification is sufficient for a pure UI overhaul.

---

## Risks & Mitigations

| Risk | Mitigation |
|---|---|
| Tailwind v4 syntax differences (it's newer) | Use `@import "tailwindcss"` + `@theme` (v4-native), not `@tailwind base/components/utilities` (v3 syntax) |
| Bootstrap JS-driven components (modals, dropdowns) drop without replacement | Replace with Vue-native `<dialog>` (BaseModal) and simple `v-if` dropdowns |
| Existing CSS uses `.collapse` etc. from Bootstrap | Replace with Vue-driven `max-height` transitions (already used in sidebar) |
| Font loading change | Keep `bunny('Instrument Sans')` from current config |

---

## What This Plan Does NOT Touch

- Backend Laravel code, routes, controllers, models
- API endpoints
- Pinia stores (auth, products, etc.) — kept as-is
- Vue Router config — kept as-is (just adds a dev-only `__UiDemo` route temporarily)
- Database schema
- Authentication flow logic
