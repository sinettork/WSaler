import { Loader2 } from "lucide-react"
import { Navigate, Outlet, useLocation } from "react-router-dom"

import { useAuth } from "@/hooks/useAuth"

/**
 * Route guard mirroring the legacy Vue router's `meta.requiresAuth` check:
 * redirects unauthenticated visitors to /login, preserving the originally
 * requested location so login can send them back afterwards.
 */
export function RequireAuth() {
  const { isAuthenticated, isInitializing } = useAuth()
  const location = useLocation()

  if (isInitializing) {
    return (
      <div className="flex h-screen items-center justify-center bg-slate-50">
        <Loader2 className="size-6 animate-spin text-brand-600" aria-label="Loading" />
      </div>
    )
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" state={{ from: location }} replace />
  }

  return <Outlet />
}
