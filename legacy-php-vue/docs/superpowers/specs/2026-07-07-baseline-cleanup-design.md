# Baseline Cleanup — Design Spec

> **Status:** Draft for review
> **Date:** 2026-07-07
> **Author:** pi agent (architecture triage session)
> **Scope:** 137 uncommitted files inventoried, grouped into 10 atomic commits.

## Context

The repo has 137 uncommitted files spanning the in-flight Tailwind refactor, an Audit Fixes Phase 1 implementation, several feature modules (RBAC, POS enhancements, inventory operations, settings, i18n), and the Cambodia address cascader plan doc. Before any architectural changes from the architecture assessment can land cleanly, the working tree needs to be baselined.

## Key findings from exploration

1. **Audit Fixes Phase 1 is implemented but uncommitted.** `AuditFixesPhase1Test.php` covers F1/F3/F4/F5/F13. The 10 modified controllers (`AuthController`, `BatchController`, `CategoryController`, `CustomerController`, `ProductController`, `Sales/SaleController`, `StockMovementController`, `SupplierController`, `UserController`, `WarehouseController`) all have `DB::transaction` + `lockForUpdate` hardening plus referential-integrity pre-checks on destroy. A full `InventoryController.php` also exists with 8 transaction-wrapped endpoints for PO/receipts/refunds/transfers/adjustments/supplier-payments.

2. **Tests already exist** (assessment claim "no test files" was false): 16 Feature tests + 5 Unit tests including `FefoBatchSelectorTest`, `RbacTest`, `PosEnhancementTest`, `InventoryOperationsTest`, `AuditFixesPhase1Test`.

3. **Tailwind refactor is partial.** New files in `components/ui/` (`PageHeader.vue`, `BaseTable.vue`) use Tailwind. `components/design/` contains 3 parallel UI exploration variants (`variant-a`, `variant-b`, `variant-c`). Legacy Vue files in `pages/` still use Bootstrap classes (`d-flex`, `btn-primary`, `card-body`, `row`, `col-md`, `nav-pills`, `table-responsive`).

4. **Cambodia address cascader implementation is already committed** (`components/AddressCascader.vue`, `i18n/index.js`); only the plan doc was untracked.

5. **The architecture assessment** that triggered this triage is partly stale: several "Critical" items (F1, F3, F4, F5, F13) already have working tests + implementation. Remaining genuinely-missing Critical items: DB engine migration (③), auth rate limiting (②), file upload validation (②), API pagination default (②).

## Commit grouping

10 atomic commits, ordered for clean rollback and bisect-friendly history:

### Commit 1: `test(rbac): add RbacTest feature coverage`

**Files:** `tests/Feature/RbacTest.php` (new, 234 lines)
**Coverage:** Permission middleware enforcement, super_admin permission set, cashier restriction set, role hierarchy
**Why first:** Foundational RBAC tests; no production code dependency.

### Commit 2: `test(pos): add PosEnhancementTest for barcode + variation lookup`

**Files:** `tests/Feature/PosEnhancementTest.php` (new, 287 lines)
**Coverage:** Barcode lookup (product + variation), POS cart pricing edge cases
**Why second:** Phase 2 enhancement tests; pairs with commit 5 (POS UX impl).

### Commit 3: `test(inventory): add InventoryOperationsTest for PO/receipts/refunds/transfers/adjustments`

**Files:** `tests/Feature/InventoryOperationsTest.php` (new, 242 lines)
**Coverage:** Purchase receipts, refunds, stock transfers, stock adjustments, sales flow integration
**Why third:** Phase 4 inventory ops tests; pairs with commit 6 (inventory UI).

### Commit 4: `feat(audit): Phase 1 fixes + AuditFixesPhase1Test (F1/F3/F4/F5/F13)`

**Files:**
- `tests/Feature/AuditFixesPhase1Test.php` (new, 423 lines)
- `app/Http/Controllers/Api/AuthController.php` (modified)
- `app/Http/Controllers/Api/BatchController.php` (modified — `DB::transaction` + `lockForUpdate` on update/destroy, F4 stock_movements pre-check)
- `app/Http/Controllers/Api/CategoryController.php` (modified — F5 referential-integrity guard)
- `app/Http/Controllers/Api/CustomerController.php` (modified — F13 referential-integrity guard)
- `app/Http/Controllers/Api/ProductController.php` (modified — `DB::transaction` wrapping variations, F5 referential-integrity guard)
- `app/Http/Controllers/Api/Sales/SaleController.php` (modified)
- `app/Http/Controllers/Api/StockMovements/StockMovementController.php` (modified)
- `app/Http/Controllers/Api/SupplierController.php` (modified — F13 referential-integrity guard)
- `app/Http/Controllers/Api/UserController.php` (modified — F5 referential-integrity guard)
- `app/Http/Controllers/Api/WarehouseController.php` (modified — `DB::transaction`, F5 stock_movements pre-check)

**Note:** `app/Http/Controllers/Api/Inventory/InventoryController.php` already has transaction-wrapped endpoints for PO/receipts/refunds/transfers/adjustments/supplier-payments, but it is **not** in the current `git status --short` modified list — it was committed in a prior phase. No action needed for commit 4.

**Why atomic:** Test + impl ship together per conventional commits; reviewable as one logical change ("audit fixes phase 1").

**Pre-merge check:** Run `php artisan test --filter=AuditFixesPhase1Test` before commit. All assertions must pass.

### Commit 5: `feat(pos): draft-orders drawer + batch-unit selector + category sidebar`

**Files:**
- `resources/js/components/BatchUnitSelector.vue` (new)
- `resources/js/components/CategorySidebar.vue` (new)
- `resources/js/components/DraftOrdersDrawer.vue` (new)
- `resources/js/stores/draftOrders.js` (new)

**Why separate from commit 4:** Frontend-only feature additions; no backend coupling to audit fixes.

### Commit 6: `feat(inventory): inventory operations pages + store`

**Files:**
- `resources/js/pages/inventory/InventoryHistoryList.vue` (new)
- `resources/js/pages/inventory/InventoryOperationForm.vue` (new)
- `resources/js/pages/inventory/InventoryOperations.vue` (new)
- `resources/js/pages/inventory/PurchaseReceiptForm.vue` (new)
- `resources/js/pages/inventory/PurchaseReceiptsList.vue` (new)
- `resources/js/pages/inventory/RefundForm.vue` (new)
- `resources/js/pages/inventory/RefundsList.vue` (new)
- `resources/js/pages/inventory/StockAdjustmentForm.vue` (new)
- `resources/js/pages/inventory/StockAdjustmentsList.vue` (new)
- `resources/js/pages/inventory/StockTransferForm.vue` (new)
- `resources/js/pages/inventory/StockTransfersList.vue` (new)
- `resources/js/stores/inventory.js` (new)

**Pairs with:** Commit 3 (test coverage) and the `InventoryController` from commit 4.

### Commit 7: `feat(settings): settings pages + store`

**Files:**
- `resources/js/pages/settings/*` (new — contents to verify at execution time)
- `resources/js/stores/settings.js` (new)

**Why separate:** Settings is its own feature surface; no cross-dependency with inventory or POS.

### Commit 8: `feat(i18n): i18next setup + composables + currency helpers + barcode composable`

**Files:**
- `resources/js/i18n/*` (new — index.js plus locale files if present)
- `resources/js/composables/currencyHelpers.js` (new)
- `resources/js/composables/useBarcodeScanner.js` (new)
- `resources/js/composables/useCurrency.js` (new)
- `resources/js/composables/useI18n.js` (new)

**Why separate:** Foundation feature; later commits may import from these composables.

### Commit 9: `feat(design): tailwind UI primitives + parallel design variants`

**Files:**
- `resources/js/components/ui/PageHeader.vue` (new — Tailwind)
- `resources/js/components/ui/BaseTable.vue` (new — Tailwind)
- `resources/js/components/design/variant-a/*` (new — Chrome, Dashboard, Login, ProductBatchList, tokens.css)
- `resources/js/components/design/variant-b/*` (new)
- `resources/js/components/design/variant-c/*` (new)
- `resources/js/pages/DesignPreview.vue` (new)

**⚠️ Blocking question — must resolve before commit 9 executes:**
- **Option A (ship all 3):** Land all three variants in their `variant-a/`, `variant-b/`, `variant-c/` directories. Useful for stakeholder A/B review via `DesignPreview.vue`.
- **Option B (pick one canonical):** Identify which variant the user wants, delete the other two, commit only the chosen one.
- **Option C (delete all three):** The exploration served its purpose; none ship. Commit only the shared primitives (`PageHeader.vue`, `BaseTable.vue`) and `DesignPreview.vue`.

Default if unanswered: **Option A** (ship all three, lowest risk to the exploration work).

### Commit 10: `docs(plans): Cambodia address cascader implementation plan`

**Files:** `docs/superpowers/plans/2026-06-27-cambodia-address-cascader.md` (new)

**Why last:** Doc-only; no code coupling. Implementation (`AddressCascader.vue`) is already committed.

## Execution order

Execute in commit order (1 → 10). For each commit:

1. `git add` the listed files (only those files — verify with `git status` first)
2. Run pre-commit check appropriate to the commit:
   - Commits 1–4: `php artisan test --filter=<TestClass>` must pass
   - Commit 4 specifically: also run `php artisan test --filter=AuditFixesPhase1Test` against the dirty tree before staging
   - Commits 5–9 (frontend): `npm run build` must succeed
3. `git commit -m "<conventional message>"` with body listing the audit finding IDs (where applicable) and a one-line rationale

## Pre-execution verification (run before commit 1)

```
git -C /mnt/d/www/Wsaler status --short         # confirm 137 file count
git -C /mnt/d/www/Wsaler diff --stat            # confirm 10 controllers modified
php artisan test --filter=AuditFixesPhase1Test   # all green before commit 4
php artisan test                                 # full suite green before commit 4
npm run build                                    # frontend builds before commits 5-9
```

## Open questions (must resolve before execution)

1. **Commit 9 variant intent:** A, B, or C? (See commit 9 section above.)
2. **Untracked migrations check:** Confirm no `database/migrations/*.php` files appear in `git status --short` output. If any exist, they belong in commit 4 (with the audit fixes).
3. **Settings pages contents:** `resources/js/pages/settings/*` was not read during spec drafting. Re-verify file list before commit 7.

## Risks

- **Unverified file list:** Inventory assumes the `git status --short` output I observed earlier is still accurate. If the user touched the tree since, regrouping may be needed. **Re-run `git status --short` before commit 1.**
- **Test green baseline:** Commits 1–4 land tests; commit 4 also lands the matching implementation. The full suite (`php artisan test`) MUST pass before commit 4. If any pre-existing tests fail against the dirty tree, those failures must be triaged separately (they are out of scope for this spec).
- **Frontend build:** Commits 5–9 land Vue files; `npm run build` MUST succeed before each. The presence of mixed Bootstrap/Tailwind in `pages/` (legacy Bootstrap classes still in place) is a known issue and out of scope for this spec — it will be addressed by sub-project ④ (finish Tailwind refactor).

## Acceptance

- `git status` is clean after all 10 commits land
- `php artisan test` passes on the clean tree
- `npm run build` succeeds on the clean tree
- `git log --oneline -15` shows the 10 new commits in order, on top of `17f7835` (current HEAD)
- Conventional Commits format throughout (`feat:`, `test:`, `fix:`, `docs:`)
