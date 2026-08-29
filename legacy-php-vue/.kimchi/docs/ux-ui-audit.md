# UX/UI Audit for Wsaler

Date: 2026-06-25
Scope: current Vue frontend UI patterns, reusable components, and modal/form popup experience.

## Executive summary

The project already has a strong foundation for a modern admin-style UI. The visual system is consistent, the layout is clean, and the reusable components are moving in the right direction. The biggest opportunity is improving the clarity of form popups so they better communicate user intent, reduce ambiguity, and feel more trustworthy.

Overall impression: solid structure, good visual consistency, moderate opportunity to improve modal intent and form flow.

## What is working well

- Consistent Tailwind-based visual language across pages and components.
- Clear page structure with headers, cards, tables, and action buttons.
- Reusable form primitives such as inputs, selects, buttons, modals, and confirmation dialogs.
- Inline validation is already present in several forms, which is a good sign for usability.

## Main UX/UI observations

### 1. Form popup intent is partly clear, but could be stronger

The popup forms used in pages like [resources/js/pages/admin/Users.vue](resources/js/pages/admin/Users.vue) and [resources/js/pages/master/Categories.vue](resources/js/pages/master/Categories.vue) do a decent job of opening a form, but they do not fully communicate intent at a glance.

Current situation:

- The modal title changes between “Add” and “Edit”, but the user still has to infer the full purpose from the fields.
- The primary action is labeled generically as “Save”, which is acceptable but not very expressive.
- There is no subtitle or short explanation that tells the user what this action will do.

Why this matters:

- Users should immediately understand whether they are creating, updating, deleting, or confirming an action.
- A popup should feel like a purposeful step, not just a floating form.

### 2. Modal design is functional but could feel more intentional

The shared modal in [resources/js/components/FormModal.vue](resources/js/components/FormModal.vue) is visually clean, but it still feels like a generic container. For better intent, the dialog should act more like a focused task surface.

Opportunity:

- Include a short helper text under the title.
- Use more specific action labels such as “Create user”, “Save changes”, or “Add category”.
- Show the object being edited in the title when appropriate, such as “Edit user: Jane Doe”.

### 3. Forms are dense and could benefit from grouping

Some forms mix core identity fields, optional metadata, and settings in a single scrollable panel. This can create cognitive load, especially for users entering data for the first time.

Examples:

- User form includes identity, password, and role in one modal.
- Category form includes name, slug, description, parent, and status in one modal.

Recommendation:

- Group fields into sections such as “Basic info”, “Access”, and “Advanced options”.
- Keep the most important fields visible first.
- Move optional fields into collapsible or secondary sections if they are not needed every time.

### 4. Validation feedback is good, but timing could be improved

Inline error messages in [resources/js/components/ui/BaseInput.vue](resources/js/components/ui/BaseInput.vue) and [resources/js/components/ui/BaseSelect.vue](resources/js/components/ui/BaseSelect.vue) are a positive pattern. However, users may still experience friction because errors appear only after submission or after interaction.

Recommendation:

- Show helpful hints for required or expected formats before the user submits.
- Highlight the first invalid field automatically after submit.
- Keep error copy short, direct, and actionable.

### 5. Modal accessibility is decent but not complete enough for a high-quality workflow

The modal components already use role and aria attributes, which is good. However, for a strong UX experience, the popup should also support better keyboard behavior.

Important improvements:

- Focus should move into the modal when it opens.
- Focus should return to the triggering button when it closes.
- Escape should close the modal consistently.
- The modal should trap focus while open.

This is especially important for [resources/js/components/FormModal.vue](resources/js/components/FormModal.vue) and [resources/js/components/ui/ConfirmDialog.vue](resources/js/components/ui/ConfirmDialog.vue).

## Popup intent audit

### Current score

- Clarity of purpose: 7/10
- Trust and confidence: 7/10
- Efficiency of form completion: 6/10
- Accessibility: 6/10
- Visual consistency: 8/10

### What the popup should communicate clearly

A good popup form should answer these questions immediately:

1. What am I doing right now?
2. What object is being created or edited?
3. What will happen when I click the main action?
4. Is this action safe, reversible, or important?

Right now, the UI is mostly clear for experienced users, but it could be more explicit for first-time users and for critical actions.

## Recommended improvements

### Priority 1: Make the popup intent obvious

- Rename generic actions to be more specific.
- Add a short subtitle under the title.
- Use stronger CTA text such as “Create user” instead of “Save”.

Example:

- “Add User” -> “Create user account”
- “Edit User” -> “Update user account”
- “Add Category” -> “Create product category”
- “Save” -> “Save changes”

### Priority 2: Improve layout for focus and scanning

- Put the primary field near the top.
- Break large forms into grouped sections.
- Use short helper text where needed.

### Priority 3: Strengthen accessible modal behavior

- Focus first input on open.
- Support Escape key.
- Trap tab focus inside modal.
- Return focus to the button that opened it.

### Priority 4: Make destructive actions feel safer

The confirmation dialog is already helpful, but it can be stronger by:

- using clearer wording,
- showing the exact object name,
- making the destructive action look clearly dangerous,
- adding a short explanation of impact.

## Suggested copy improvements

- “Add User” → “Create user account”
- “Edit User” → “Update user account”
- “Add Category” → “Create product category”
- “Delete user” → “Remove this user account?”
- “Save” → “Create category” or “Save changes”

## Conclusion

The project already has a strong UI base, and the modal/form system is moving in the right direction. The main improvement opportunity is not visual style, but intent clarity. If the popup forms communicate purpose more clearly, use more precise actions, and support better keyboard behavior, the experience will feel more professional, trustworthy, and easier to use.

If you want, I can next turn this audit into a concrete UI implementation plan or directly improve the popup components in the codebase.
