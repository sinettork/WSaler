import { Navigate, Outlet } from "react-router-dom"

import { useAuth } from "@/hooks/useAuth"
import type { UserRole } from "@/types/auth"

/**
 * Route guard mirroring the legacy Vue router's `meta.role` check: redirects
 * to /dashboard if the signed-in user doesn't hold one of the allowed roles.
 * Used for nested routes that need role gating in addition to auth
 * (e.g. /admin/users is admin-only).
 */
export function RequireRole({ roles }: { roles: UserRole[] }) {
  const { hasRole } = useAuth()

  if (!hasRole(roles)) {
    return <Navigate to="/dashboard" replace />
  }

  return <Outlet />
}
