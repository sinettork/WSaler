# Sidebar Behavior Fixes

## Issues Found and Fixed

### 1. **POS Button Always Active**
**Problem:** The POS button was always styled with `bg-brand-600` making it look active even when on other pages.

**Fix:** Added conditional styling based on active route:
```vue
:class="isActive('/pos')
    ? 'bg-brand-600 text-white hover:bg-brand-700'
    : 'bg-brand-600/10 text-brand-600 hover:bg-brand-600 hover:text-white'"
```

**Result:** POS button now shows inactive state when you're on other pages.

---

### 2. **Missing Translation for "Inventory Operations"**
**Problem:** The menu item was hardcoded as "Inventory operations" instead of using i18n.

**Fix:** 
- Added translation key `nav.inventoryOperations` to both English and Khmer locale files
- Updated sidebar component to use `$t('nav.inventoryOperations')`

**Result:** Menu properly translates between English and Khmer.

---

### 3. **Sections Not Auto-Expanding on Navigation**
**Problem:** When navigating directly to a nested route (e.g., `/products`), the parent section wouldn't automatically expand.

**Fix:** Added route watcher to auto-expand sections containing the active route:
```javascript
watch(() => route.path, () => {
    for (const section of sections) {
        if (isSectionActive(section)) {
            expanded.value[section.id] = true;
            saveExpanded();
        }
    }
});
```

**Result:** Sections automatically expand when you navigate to any of their child routes, improving UX.

---

## Testing Checklist

- [ ] POS button shows inactive state when on Dashboard
- [ ] POS button shows active state when on `/pos` page
- [ ] Clicking on "Products" auto-expands the "Products" section
- [ ] Clicking on "Categories" auto-expands the "Master Data" section
- [ ] Section expansion state persists after page refresh
- [ ] Translations work in both English and Khmer
- [ ] All menu items are accessible based on user roles

---

## Files Modified

1. `resources/js/components/AppSidebar.vue`
   - Fixed POS button conditional styling
   - Changed "Inventory operations" to use i18n key
   - Added route watcher for auto-expansion

2. `resources/js/i18n/locales/en.json`
   - Added `nav.inventoryOperations: "Inventory Operations"`

3. `resources/js/i18n/locales/km.json`
   - Added `nav.inventoryOperations: "ប្រតិបត្តិការសារពើភណ្ឌ"`

---

## Additional Improvements Made

- Better visual feedback for POS button states
- Smoother user experience when navigating between sections
- Consistent use of translation keys throughout the sidebar
- Section expansion state properly syncs with localStorage

---

## Before & After

### Before:
❌ POS button always looked "active" (brand-600 background)
❌ "Inventory operations" hardcoded in English
❌ Sections didn't auto-expand when navigating to child routes

### After:
✅ POS button shows inactive state with subtle brand-600/10 background
✅ All menu items use proper i18n translations
✅ Sections intelligently expand when you navigate to their routes
✅ Better visual hierarchy and user feedback
