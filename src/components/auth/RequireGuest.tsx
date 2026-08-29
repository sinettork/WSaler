import { Loader2 } from "lucide-react"
import { Navigate, Outlet } from "react-router-dom"

import { useAuth } from "@/hooks/useAuth"

/**
 * Route guard mirroring the legacy Vue router's `meta.public` check on
 * /login and /register: an already-authenticated visitor is redirected
 * to the dashboard instead of seeing the login/register form again.
 */
export function RequireGuest() {
  const { isAuthenticated, isInitializing } = useAuth()

  if (isInitializing) {
    return (
      <div className="flex h-screen items-center justify-center bg-slate-50">
        <Loader2 className="size-6 animate-spin text-brand-600" aria-label="Loading" />
      </div>
    )
  }

  if (isAuthenticated) {
    return <Navigate to="/dashboard" replace />
  }

  return <Outlet />
}
