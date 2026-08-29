import { useAuthStore } from "@/stores/auth"

/**
 * Convenience hook mirroring the legacy Pinia `useAuthStore()` getters
 * (isAuthenticated, hasRole, hasPermission) so feature code can read
 * `const { profile, hasPermission } = useAuth()` without importing the
 * Zustand store directly everywhere.
 */
export function useAuth() {
  const session = useAuthStore((s) => s.session)
  const user = useAuthStore((s) => s.user)
  const profile = useAuthStore((s) => s.profile)
  const isInitializing = useAuthStore((s) => s.isInitializing)
  const isSubmitting = useAuthStore((s) => s.isSubmitting)
  const error = useAuthStore((s) => s.error)
  const login = useAuthStore((s) => s.login)
  const register = useAuthStore((s) => s.register)
  const logout = useAuthStore((s) => s.logout)
  const clearError = useAuthStore((s) => s.clearError)
  const hasRole = useAuthStore((s) => s.hasRole)
  const hasPermission = useAuthStore((s) => s.hasPermission)

  return {
    session,
    user,
    profile,
    isAuthenticated: Boolean(session),
    isInitializing,
    isSubmitting,
    error,
    login,
    register,
    logout,
    clearError,
    hasRole,
    hasPermission,
  }
}
