import { nextTick } from 'vue';

/**
 * useFormErrors(errors)
 *
 * Returns helpers bound to a specific reactive errors object so call sites
 * stay short and there is no risk of mixing up the errors reference.
 *
 * Usage:
 *   const errors = reactive({});
 *   const { focusFirstError, clearErrors, clearError } = useFormErrors(errors);
 *
 *   // in template:
 *   @input="clearError('name')"
 *
 *   // in submit handler:
 *   catch (e) {
 *     if (e.fieldErrors) {
 *       Object.assign(errors, e.fieldErrors);
 *       focusFirstError();
 *     }
 *   }
 */
export function useFormErrors(errors) {
    async function focusFirstError() {
        if (!errors) return false;
        const fieldNames = Object.keys(errors);
        if (fieldNames.length === 0) return false;

        await nextTick();

        for (const name of fieldNames) {
            const el = document.querySelector(`[name="${name}"]`);
            if (el && typeof el.focus === 'function') {
                el.focus({ preventScroll: false });
                if (typeof el.scrollIntoView === 'function') {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return true;
            }
        }
        return false;
    }

    function clearErrors() {
        if (!errors) return;
        Object.keys(errors).forEach((k) => delete errors[k]);
    }

    function clearError(field) {
        if (errors && errors[field]) delete errors[field];
    }

    return { focusFirstError, clearErrors, clearError };
}
