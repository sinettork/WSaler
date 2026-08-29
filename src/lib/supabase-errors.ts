import type { PostgrestError } from "@supabase/supabase-js"

export interface FieldErrors {
  [field: string]: string
}

/**
 * Best-effort translation of common Postgres/PostgREST error codes into a
 * message safe to show end users, plus (when we can confidently guess the
 * offending column) a field-level error suitable for react-hook-form's
 * `setError()`. This is the closest React/Supabase equivalent of Laravel's
 * `$e->fieldErrors` validation-exception shape used throughout the legacy
 * Vue forms.
 */
export function parseSupabaseError(
  error: PostgrestError | Error | null | undefined,
): { message: string; fieldErrors: FieldErrors } {
  if (!error) return { message: "Something went wrong. Please try again.", fieldErrors: {} }

  const pgError = error as Partial<PostgrestError> & Error
  const code = pgError.code
  const details = pgError.details ?? ""
  const message = pgError.message ?? "Something went wrong. Please try again."

  if (code === "23505") {
    // unique_violation
    const match = /Key \(([^)]+)\)=/.exec(details) ?? /Key \(([^)]+)\)/.exec(message)
    const column = match?.[1]?.split(",")[0]?.trim()
    if (column) {
      const label = column.replace(/_/g, " ")
      const friendly = `This ${label} is already in use.`
      return { message: friendly, fieldErrors: { [column]: friendly } }
    }
    return { message: "This record already exists.", fieldErrors: {} }
  }

  if (code === "23503") {
    // foreign_key_violation
    return {
      message: "This record is referenced by other data and cannot be changed this way.",
      fieldErrors: {},
    }
  }

  if (code === "23514") {
    // check_violation
    return { message: "This value is not allowed.", fieldErrors: {} }
  }

  if (code === "42501" || code === "PGRST301") {
    return { message: "You don't have permission to do this.", fieldErrors: {} }
  }

  return { message, fieldErrors: {} }
}

/** True when an `.update().eq('id', id).eq('version', v).select().single()` returned
 * no row — i.e. either the row was deleted, or (far more likely) someone else
 * updated it first and the optimistic-locking version no longer matches. */
export function isOptimisticLockMiss(error: PostgrestError | null | undefined): boolean {
  return error?.code === "PGRST116"
}
